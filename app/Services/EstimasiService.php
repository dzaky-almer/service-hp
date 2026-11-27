<?php

namespace App\Services;

use App\Models\Estimasi;
use App\Models\ItemEstimasi;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Models\Sparepart;
use App\Models\TarifJasa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Estimasi biaya per versi (fitur 24, 25, 27, 28).
 *
 * - Harga item hanya berasal dari master (tarif jasa / harga jual spare part),
 *   disalin sebagai snapshot; harga dari form diabaikan (BR-08, SnapshotHarga).
 * - Total selalu dihitung server dari item.
 * - `disetujui`/`dikirim` tidak dapat diedit: perubahan = `revisi()`.
 */
class EstimasiService
{
    public function __construct(
        private readonly ServisService $servis,
        private readonly AuditService $audit,
    ) {}

    /**
     * Buat atau perbarui draf estimasi. Draf yang sudah ada diganti itemnya.
     *
     * @param  list<array{sumber: string, jumlah: int}>  $items
     */
    public function simpanDraf(Servis $servis, array $items, Pengguna $pelaku): Estimasi
    {
        return DB::transaction(function () use ($servis, $items, $pelaku): Estimasi {
            $baris = Servis::whereKey($servis->id_servis)->lockForUpdate()->firstOrFail();
            $this->pastikanBolehDiisi($baris);

            $draf = $baris->estimasi()->reorder('versi', 'desc')->where('status', Estimasi::DRAF)->first();

            if ($draf === null) {
                $draf = $baris->estimasi()->create([
                    'versi' => (int) $baris->estimasi()->max('versi') + 1,
                    'status' => Estimasi::DRAF,
                    'dibuat_oleh' => $pelaku->id_pengguna,
                    'dibuat_pada' => now(),
                ]);
            }

            $draf->item()->delete();

            foreach ($items as $item) {
                $this->tambahItem($draf, (string) $item['sumber'], (int) $item['jumlah']);
            }

            $this->audit->catat('simpan_estimasi_draf', $baris, null, [
                'versi' => $draf->versi,
                'jumlah_item' => count($items),
            ], $pelaku);

            return $draf->load('item');
        });
    }

    /** Validasi dan kirim estimasi ke customer (fitur 25). */
    public function kirim(Estimasi $estimasi, Pengguna $pelaku): Estimasi
    {
        return DB::transaction(function () use ($estimasi, $pelaku): Estimasi {
            $baris = Estimasi::whereKey($estimasi->id_estimasi)->lockForUpdate()->firstOrFail();
            $servis = Servis::whereKey($baris->id_servis)->lockForUpdate()->firstOrFail();

            if ($baris->status !== Estimasi::DRAF) {
                throw ValidationException::withMessages(['estimasi' => 'Estimasi ini sudah dikirim atau sudah diputuskan.']);
            }

            if (! $servis->bolehDiagnosis()) {
                throw ValidationException::withMessages(['estimasi' => 'Estimasi hanya dapat dikirim saat servis menunggu diagnosis atau sedang dikerjakan.']);
            }

            if (! $servis->diagnosis()->exists()) {
                throw ValidationException::withMessages(['estimasi' => 'Isi diagnosis sebelum mengirim estimasi.']);
            }

            $baris->load('item');

            if ($baris->item->isEmpty()) {
                throw ValidationException::withMessages(['estimasi' => 'Estimasi harus punya minimal satu item.']);
            }

            foreach ($baris->item as $item) {
                if ($item->jenis === ItemEstimasi::SPAREPART) {
                    $sparepart = $item->sparepart;

                    if ($sparepart === null || $sparepart->status !== Sparepart::STATUS_AKTIF) {
                        throw ValidationException::withMessages([
                            'estimasi' => 'Item "'.$item->deskripsi.'" merujuk spare part yang nonaktif. Ganti atau hapus item itu.',
                        ]);
                    }
                }
            }

            $baris->forceFill([
                'status' => Estimasi::DIKIRIM,
                'divalidasi_oleh' => $pelaku->id_pengguna,
                'dikirim_pada' => now(),
            ])->save();

            $this->servis->pindahStatus($servis, Servis::MENUNGGU_PERSETUJUAN, $pelaku);
            $this->audit->catat('kirim_estimasi', $servis, null, [
                'versi' => $baris->versi,
                'total' => $baris->total(),
            ], $pelaku);

            return $baris;
        });
    }

    /**
     * Revisi estimasi (fitur 28): versi baru berisi salinan item versi disetujui
     * terakhir, dengan harga di-snapshot ulang dari master yang masih aktif.
     */
    public function revisi(Servis $servis, Pengguna $pelaku): Estimasi
    {
        return DB::transaction(function () use ($servis, $pelaku): Estimasi {
            $baris = Servis::whereKey($servis->id_servis)->lockForUpdate()->firstOrFail();

            if (! $baris->bolehDiagnosis()) {
                throw ValidationException::withMessages(['estimasi' => 'Revisi hanya dapat dibuat saat servis menunggu diagnosis atau sedang dikerjakan.']);
            }

            $draf = $baris->estimasi()->reorder('versi', 'desc')->where('status', Estimasi::DRAF)->first();

            if ($draf !== null) {
                return $draf->load('item');
            }

            $sumber = $baris->estimasiDisetujui() ?? $baris->estimasiTerakhir();

            $draf = $baris->estimasi()->create([
                'versi' => (int) $baris->estimasi()->max('versi') + 1,
                'status' => Estimasi::DRAF,
                'dibuat_oleh' => $pelaku->id_pengguna,
                'dibuat_pada' => now(),
            ]);

            foreach ($sumber?->item ?? [] as $lama) {
                $sumberBaru = $lama->id_sparepart !== null
                    ? ItemEstimasi::SPAREPART.':'.$lama->id_sparepart
                    : ItemEstimasi::JASA.':'.$lama->id_tarif;

                try {
                    $this->tambahItem($draf, $sumberBaru, $lama->jumlah);
                } catch (ValidationException) {
                    // Master sudah nonaktif/dihapus: pertahankan snapshot lama apa adanya.
                    $draf->item()->create($lama->only([
                        'jenis', 'deskripsi', 'id_sparepart', 'id_tarif', 'jumlah', 'harga_satuan_salinan',
                    ]));
                }
            }

            $this->audit->catat('revisi_estimasi', $baris, null, [
                'versi' => $draf->versi,
                'sumber_versi' => $sumber?->versi,
            ], $pelaku);

            return $draf->load('item');
        });
    }

    /**
     * Keputusan customer (fitur 27). Hanya pemilik servis; baris dikunci sehingga
     * keputusan kedua melihat status bukan `dikirim` dan ditolak.
     */
    public function putuskan(Estimasi $estimasi, bool $setuju, Pengguna $pelaku, ?string $catatan = null): Estimasi
    {
        return DB::transaction(function () use ($estimasi, $setuju, $pelaku, $catatan): Estimasi {
            $baris = Estimasi::whereKey($estimasi->id_estimasi)->lockForUpdate()->firstOrFail();
            $servis = Servis::whereKey($baris->id_servis)->lockForUpdate()->firstOrFail();

            if ($pelaku->peran !== Pengguna::PERAN_CUSTOMER
                || $servis->pelanggan?->id_pengguna !== $pelaku->id_pengguna) {
                throw ValidationException::withMessages(['estimasi' => 'Keputusan estimasi hanya boleh dilakukan pemilik servis.']);
            }

            if ($baris->status !== Estimasi::DIKIRIM) {
                throw ValidationException::withMessages(['estimasi' => 'Estimasi ini sudah diputuskan sebelumnya.']);
            }

            if ($servis->status !== Servis::MENUNGGU_PERSETUJUAN) {
                throw ValidationException::withMessages(['estimasi' => 'Servis ini tidak sedang menunggu persetujuan.']);
            }

            $revisi = $servis->pernahDikerjakan();

            if ($setuju) {
                $servis->estimasi()
                    ->where('status', Estimasi::DISETUJUI)
                    ->whereKeyNot($baris->id_estimasi)
                    ->update(['status' => Estimasi::DIGANTIKAN]);
            }

            $baris->forceFill([
                'status' => $setuju ? Estimasi::DISETUJUI : Estimasi::DITOLAK,
                'diputuskan_oleh' => $pelaku->id_pengguna,
                'diputuskan_pada' => now(),
                'catatan_customer' => $catatan,
            ])->save();

            $tujuan = match (true) {
                $setuju && $revisi => Servis::DIKERJAKAN,
                $setuju => Servis::DISETUJUI,
                $revisi => Servis::DIKERJAKAN,
                default => Servis::DITOLAK,
            };

            $this->servis->pindahStatus($servis, $tujuan, $pelaku, $catatan);

            $this->audit->catat(
                $setuju ? 'setujui_estimasi' : 'tolak_estimasi',
                $servis,
                ['status_estimasi' => Estimasi::DIKIRIM],
                ['status_estimasi' => $baris->status, 'versi' => $baris->versi],
                $pelaku,
            );

            return $baris;
        });
    }

    /** Opsi dropdown sumber item untuk form draf estimasi. */
    public static function pilihanSumber(): array
    {
        $jasa = TarifJasa::where('status', TarifJasa::STATUS_AKTIF)->orderBy('nama')->get()
            ->mapWithKeys(fn (TarifJasa $t) => [
                ItemEstimasi::JASA.':'.$t->id_tarif => 'Jasa — '.$t->nama.' ('.rupiah($t->harga).')',
            ]);

        $sparepart = Sparepart::where('status', Sparepart::STATUS_AKTIF)->orderBy('nama')->get()
            ->mapWithKeys(fn (Sparepart $s) => [
                ItemEstimasi::SPAREPART.':'.$s->id_sparepart => 'Spare part — '.$s->nama.' ('.rupiah($s->harga_jual).')',
            ]);

        return $jasa->union($sparepart)->all();
    }

    private function pastikanBolehDiisi(Servis $servis): void
    {
        if (! $servis->bolehDiagnosis()) {
            throw ValidationException::withMessages([
                'estimasi' => 'Estimasi hanya dapat dibuat saat servis menunggu diagnosis atau sedang dikerjakan.',
            ]);
        }
    }

    /** Sumber item berbentuk "jasa:{id_tarif}" atau "sparepart:{id_sparepart}". */
    private function tambahItem(Estimasi $estimasi, string $sumber, int $jumlah): void
    {
        [$jenis, $id] = array_pad(explode(':', $sumber, 2), 2, '');

        if ($jumlah < 1) {
            throw ValidationException::withMessages(['items' => 'Jumlah item minimal 1.']);
        }

        if ($jenis === ItemEstimasi::JASA) {
            $tarif = TarifJasa::where('id_tarif', $id)->where('status', TarifJasa::STATUS_AKTIF)->first();

            if ($tarif === null) {
                throw ValidationException::withMessages(['items' => 'Tarif jasa tidak ditemukan atau sudah nonaktif.']);
            }

            $estimasi->item()->create([
                'jenis' => ItemEstimasi::JASA,
                'deskripsi' => mb_substr($tarif->nama, 0, 150),
                'id_tarif' => $tarif->id_tarif,
                'jumlah' => $jumlah,
                'harga_satuan_salinan' => $tarif->harga,
            ]);

            return;
        }

        if ($jenis === ItemEstimasi::SPAREPART) {
            $sparepart = Sparepart::where('id_sparepart', $id)->where('status', Sparepart::STATUS_AKTIF)->first();

            if ($sparepart === null) {
                throw ValidationException::withMessages(['items' => 'Spare part tidak ditemukan atau sudah nonaktif.']);
            }

            $estimasi->item()->create([
                'jenis' => ItemEstimasi::SPAREPART,
                'deskripsi' => mb_substr($sparepart->nama, 0, 150),
                'id_sparepart' => $sparepart->id_sparepart,
                'jumlah' => $jumlah,
                'harga_satuan_salinan' => $sparepart->harga_jual,
            ]);

            return;
        }

        throw ValidationException::withMessages(['items' => 'Jenis item estimasi tidak dikenal.']);
    }
}
