<?php

namespace App\Services;

use App\Models\FotoServis;
use App\Models\PengaturanBisnis;
use App\Models\Pengguna;
use App\Models\Servis;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aturan bisnis service order: pembuatan (snapshot SLA + biaya pemeriksaan),
 * mesin transisi status, assign teknisi, pembatalan, foto (fitur 15–22).
 *
 * `servis.status` hanya berubah di sini (ARCHITECTURE §3.2).
 */
class ServisService
{
    /**
     * Tabel transisi yang sah: dari status => status tujuan yang diizinkan.
     * Syarat tambahan per transisi (mis. lunas saat serah terima) diperiksa
     * pemanggilnya di tahap fitur terkait.
     *
     * ponytail: hanya peta status; syarat dokumen (estimasi/QC/pembayaran) masuk
     * saat fiturnya dibuat (Tahap 4–6).
     */
    public const TRANSISI = [
        Servis::DITERIMA => [Servis::MENUNGGU_DIAGNOSIS, Servis::DIBATALKAN],
        Servis::MENUNGGU_DIAGNOSIS => [Servis::MENUNGGU_PERSETUJUAN, Servis::DIBATALKAN],
        Servis::MENUNGGU_PERSETUJUAN => [Servis::DISETUJUI, Servis::DITOLAK, Servis::DIBATALKAN],
        Servis::DISETUJUI => [Servis::DIKERJAKAN, Servis::DIBATALKAN],
        Servis::DIKERJAKAN => [Servis::QUALITY_CONTROL, Servis::MENUNGGU_PERSETUJUAN, Servis::DIBATALKAN],
        Servis::QUALITY_CONTROL => [Servis::SIAP_DIAMBIL, Servis::DIKERJAKAN, Servis::DIBATALKAN],
        Servis::DITOLAK => [Servis::SIAP_DIAMBIL, Servis::DIBATALKAN],
        Servis::SIAP_DIAMBIL => [Servis::SELESAI, Servis::DIBATALKAN],
        Servis::SELESAI => [],
        Servis::DIBATALKAN => [],
    ];

    public function __construct(
        private readonly NomorServisGenerator $nomor,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data  sudah divalidasi `ServisRequest`
     */
    public function buatServis(array $data, Pengguna $pelaku): Servis
    {
        return DB::transaction(function () use ($data, $pelaku): Servis {
            $nomor = $this->nomor->berikutnya();
            $sekarang = now();

            $servis = new Servis($data);
            $servis->forceFill([
                'nomor_servis' => $nomor['nomor_servis'],
                'kode_tracking' => $nomor['kode_tracking'],
                'sla_hari_salinan' => (int) PengaturanBisnis::nilai('sla_hari', 7),
                'biaya_pemeriksaan_salinan' => (float) PengaturanBisnis::nilai('biaya_pemeriksaan', 0),
                'status' => Servis::DITERIMA,
                'tanggal_masuk' => $sekarang,
                'dibuat_oleh' => $pelaku->id_pengguna,
                'dibuat_pada' => $sekarang,
                'diperbarui_pada' => $sekarang,
            ])->save();

            $this->catatRiwayat($servis, null, Servis::DITERIMA, $pelaku, null);
            $this->audit->catat('buat_servis', $servis, null, [
                'nomor_servis' => $servis->nomor_servis,
                'status' => $servis->status,
                'id_pelanggan' => $servis->id_pelanggan,
            ], $pelaku);

            return $servis;
        });
    }

    public function pindahStatus(Servis $servis, string $ke, Pengguna $pelaku, ?string $alasan = null): Servis
    {
        return DB::transaction(function () use ($servis, $ke, $pelaku, $alasan): Servis {
            $baris = Servis::whereKey($servis->id_servis)->lockForUpdate()->firstOrFail();
            $dari = $baris->status;

            if (! in_array($ke, self::TRANSISI[$dari] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status tidak dapat berpindah dari '.($baris->labelStatus()).' ke '.(Servis::LABEL_STATUS[$ke] ?? $ke).'.',
                ]);
            }

            $baris->forceFill(['status' => $ke, 'diperbarui_pada' => now()])->save();
            $this->catatRiwayat($baris, $dari, $ke, $pelaku, $alasan);

            $this->audit->catat('pindah_status_servis', $baris, ['status' => $dari], ['status' => $ke], $pelaku);

            return $baris;
        });
    }

    /**
     * Ubah informasi perangkat/keluhan (fitur 18). Pelanggan dan status tidak diubah di sini.
     *
     * @param  array<string, mixed>  $data
     */
    public function ubahInfo(Servis $servis, array $data, Pengguna $pelaku): Servis
    {
        unset($data['id_pelanggan']);

        $sebelum = $servis->only(array_keys($data));

        return DB::transaction(function () use ($servis, $data, $sebelum, $pelaku): Servis {
            $servis->forceFill($data + ['diperbarui_pada' => now()])->save();

            $this->audit->catat('ubah_servis', $servis, $sebelum, $servis->only(array_keys($data)), $pelaku);

            return $servis;
        });
    }

    public function assignTeknisi(Servis $servis, Pengguna $teknisi, Pengguna $pelaku): Servis
    {
        if ($teknisi->peran !== Pengguna::PERAN_TEKNISI || $teknisi->status_akun !== Pengguna::STATUS_AKTIF) {
            throw ValidationException::withMessages(['id_teknisi' => 'Teknisi yang dipilih tidak aktif.']);
        }

        return DB::transaction(function () use ($servis, $teknisi, $pelaku): Servis {
            $baris = Servis::whereKey($servis->id_servis)->lockForUpdate()->firstOrFail();

            if (in_array($baris->status, Servis::STATUS_AKHIR, true)) {
                throw ValidationException::withMessages(['id_teknisi' => 'Servis sudah selesai atau dibatalkan.']);
            }

            $sebelum = $baris->id_teknisi;
            $baris->forceFill(['id_teknisi' => $teknisi->id_pengguna, 'diperbarui_pada' => now()])->save();

            $this->audit->catat('assign_teknisi', $baris, ['id_teknisi' => $sebelum], ['id_teknisi' => $teknisi->id_pengguna], $pelaku);

            // Assign pertama memindahkan servis ke Menunggu Diagnosis (fitur 20).
            if ($baris->status === Servis::DITERIMA) {
                return $this->pindahStatus($baris, Servis::MENUNGGU_DIAGNOSIS, $pelaku);
            }

            return $baris;
        });
    }

    public function batalkan(Servis $servis, Pengguna $pelaku, string $alasan): Servis
    {
        $alasan = trim($alasan);

        if ($alasan === '') {
            throw ValidationException::withMessages(['alasan' => 'Alasan pembatalan wajib diisi.']);
        }

        if (in_array($servis->status, Servis::STATUS_AKHIR, true)) {
            throw ValidationException::withMessages(['status' => 'Servis sudah selesai atau sudah dibatalkan.']);
        }

        if ($pelaku->peran === Pengguna::PERAN_ADMIN && ! in_array($servis->status, Servis::BATAL_OLEH_ADMIN, true)) {
            throw ValidationException::withMessages(['status' => 'Servis pada status ini hanya dapat dibatalkan Owner.']);
        }

        return $this->pindahStatus($servis, Servis::DIBATALKAN, $pelaku, $alasan);
    }

    public function tambahFoto(Servis $servis, UploadedFile $file, string $jenis, Pengguna $pelaku): FotoServis
    {
        $path = $file->store('servis/'.$servis->id_servis, 'local');

        $foto = FotoServis::create([
            'id_servis' => $servis->id_servis,
            'jenis' => $jenis,
            'path_file' => $path,
            'diunggah_oleh' => $pelaku->id_pengguna,
            'waktu' => now(),
        ]);

        $this->audit->catat('unggah_foto_servis', $servis, null, ['foto' => $path, 'jenis' => $jenis], $pelaku);

        return $foto;
    }

    private function catatRiwayat(Servis $servis, ?string $dari, string $ke, Pengguna $pelaku, ?string $alasan): void
    {
        $servis->riwayat()->create([
            'dari_status' => $dari,
            'ke_status' => $ke,
            'diubah_oleh' => $pelaku->id_pengguna,
            'alasan' => $alasan,
            'waktu' => now(),
        ]);
    }
}
