<?php

namespace App\Services;

use App\Models\CatatanPengerjaan;
use App\Models\MutasiStok;
use App\Models\Pengguna;
use App\Models\PenggunaanSparepart;
use App\Models\QcHasil;
use App\Models\Servis;
use App\Models\Sparepart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Proses servis (fitur 29–34, 37): ubah status pengerjaan, catatan, pemakaian
 * spare part, QC, dan tanda siap diambil. Semua perubahan status tetap lewat
 * `ServisService::pindahStatus()` (ARCHITECTURE §3.2).
 */
class PengerjaanService
{
    /** Butir QC tetap; satu baris `qc_hasil` per butir per putaran (ARCHITECTURE §3.4). */
    public const BUTIR_QC = ['Booting', 'Charging', 'Keyboard', 'Layar', 'Wi-Fi', 'Suara'];

    /** Transisi yang bisa dipicu dari layar pengerjaan (sisanya lewat aksi khusus). */
    public const TRANSISI_PENGERJAAN = [
        Servis::DISETUJUI => [Servis::DIKERJAKAN],
        Servis::DIKERJAKAN => [Servis::QUALITY_CONTROL],
    ];

    public function __construct(
        private readonly ServisService $servis,
        private readonly StokService $stok,
        private readonly AuditService $audit,
    ) {}

    /** Fitur 30: mulai pengerjaan / selesai perbaikan. */
    public function ubahStatus(Servis $servis, string $ke, Pengguna $pelaku, ?string $alasan = null): Servis
    {
        $dari = $servis->status;

        if (! in_array($ke, self::TRANSISI_PENGERJAAN[$dari] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Status tidak dapat berpindah dari '.$servis->labelStatus().' ke '.(Servis::LABEL_STATUS[$ke] ?? $ke).'.',
            ]);
        }

        return $this->servis->pindahStatus($servis, $ke, $pelaku, $alasan);
    }

    /** Fitur 31: catatan pengerjaan (append-only). */
    public function catatCatatan(Servis $servis, string $isi, Pengguna $pelaku): CatatanPengerjaan
    {
        if (! $servis->bolehCatatan()) {
            throw ValidationException::withMessages([
                'isi' => 'Catatan hanya bisa ditulis setelah estimasi disetujui.',
            ]);
        }

        return DB::transaction(function () use ($servis, $isi, $pelaku) {
            $catatan = CatatanPengerjaan::create([
                'id_servis' => $servis->id_servis,
                'id_pengguna' => $pelaku->id_pengguna,
                'isi' => $isi,
                'waktu' => now(),
            ]);

            $this->audit->catat('catat_pengerjaan', $servis, null, [
                'id_catatan' => $catatan->id_catatan,
                'ringkas' => mb_substr($isi, 0, 120),
            ], $pelaku);

            return $catatan;
        });
    }

    /**
     * Fitur 37: catat pemakaian spare part. Mutasi keluar + baris pemakaian dengan
     * snapshot harga beli, satu transaksi; baris spare part dikunci (BR-13).
     */
    public function catatPenggunaan(Servis $servis, Sparepart $sparepart, int $jumlah, Pengguna $pelaku): PenggunaanSparepart
    {
        if ($servis->status !== Servis::DIKERJAKAN) {
            throw ValidationException::withMessages([
                'status' => 'Spare part hanya dicatat saat servis berstatus Dikerjakan.',
            ]);
        }

        return DB::transaction(function () use ($servis, $sparepart, $jumlah, $pelaku) {
            $terkunci = Sparepart::query()->whereKey($sparepart->getKey())->lockForUpdate()->firstOrFail();

            if ($terkunci->status !== Sparepart::STATUS_AKTIF) {
                throw ValidationException::withMessages(['id_sparepart' => 'Spare part tidak aktif.']);
            }

            $stok = $this->stok->stok($terkunci);
            if ($stok - $jumlah < 0) {
                throw ValidationException::withMessages([
                    'jumlah' => "Stok {$terkunci->nama} tinggal {$stok}.",
                ]);
            }

            $waktu = now();

            $mutasi = MutasiStok::create([
                'id_sparepart' => $terkunci->id_sparepart,
                'jenis' => MutasiStok::JENIS_KELUAR_SERVIS,
                'jumlah' => -$jumlah,
                'id_servis' => $servis->id_servis,
                'dibuat_oleh' => $pelaku->id_pengguna,
                'waktu' => $waktu,
            ]);

            $pemakaian = PenggunaanSparepart::create([
                'id_servis' => $servis->id_servis,
                'id_sparepart' => $terkunci->id_sparepart,
                'id_mutasi' => $mutasi->id_mutasi,
                'jumlah' => $jumlah,
                'harga_beli_salinan' => $terkunci->harga_beli,
                'dicatat_oleh' => $pelaku->id_pengguna,
                'waktu' => $waktu,
            ]);

            $this->audit->catat('pakai_sparepart', $servis, null, [
                'id_penggunaan' => $pemakaian->id_penggunaan,
                'id_sparepart' => $terkunci->id_sparepart,
                'jumlah' => $jumlah,
                'stok' => $this->stok->stok($terkunci),
            ], $pelaku);

            return $pemakaian;
        });
    }

    /**
     * Fitur 33: isi QC satu putaran. Ada butir gagal → servis kembali dikerjakan;
     * semua lulus → tetap `quality_control`, siap ditandai siap diambil.
     *
     * @param  array<string, string>  $hasil  butir => 'lulus'|'tidak_lulus'
     * @return array{putaran: int, lulus: bool}
     */
    public function catatQc(Servis $servis, array $hasil, Pengguna $pelaku): array
    {
        if ($servis->status !== Servis::QUALITY_CONTROL) {
            throw ValidationException::withMessages([
                'status' => 'QC hanya bisa diisi saat servis berstatus Quality Control.',
            ]);
        }

        return DB::transaction(function () use ($servis, $hasil, $pelaku) {
            // Kunci baris servis supaya dua pengisian bersamaan tidak memakai putaran sama.
            $baris = Servis::query()->whereKey($servis->getKey())->lockForUpdate()->firstOrFail();

            $putaran = (int) DB::table('qc_hasil')->where('id_servis', $baris->id_servis)->max('putaran') + 1;
            $lulus = true;

            foreach (self::BUTIR_QC as $butir) {
                $nilai = $hasil[$butir] ?? 'tidak_lulus';
                $butirLulus = $nilai === 'lulus';
                $lulus = $lulus && $butirLulus;

                QcHasil::create([
                    'id_servis' => $baris->id_servis,
                    'putaran' => $putaran,
                    'butir' => $butir,
                    'lulus' => $butirLulus,
                    'dicek_oleh' => $pelaku->id_pengguna,
                    'waktu' => now(),
                ]);
            }

            $this->audit->catat('qc_selesai', $baris, null, [
                'putaran' => $putaran,
                'lulus' => $lulus,
            ], $pelaku);

            if (! $lulus) {
                $this->servis->pindahStatus($baris, Servis::DIKERJAKAN, $pelaku, "QC putaran {$putaran} tidak lulus.");
            }

            return ['putaran' => $putaran, 'lulus' => $lulus];
        });
    }

    /** Fitur 34: siap diambil hanya setelah putaran QC terakhir lulus semua. */
    public function siapDiambil(Servis $servis, Pengguna $pelaku): Servis
    {
        if ($servis->status !== Servis::QUALITY_CONTROL) {
            throw ValidationException::withMessages([
                'status' => 'Siap diambil hanya bisa ditandai dari tahap Quality Control.',
            ]);
        }

        if (! $servis->qcLulusTerakhir()) {
            throw ValidationException::withMessages([
                'status' => 'QC belum lulus semua; perbaiki dulu.',
            ]);
        }

        return $this->servis->pindahStatus($servis, Servis::SIAP_DIAMBIL, $pelaku);
    }
}
