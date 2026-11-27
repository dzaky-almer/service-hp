<?php

namespace App\Services;

use App\Models\MutasiStok;
use App\Models\Pengguna;
use App\Models\Sparepart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StokService
{
    public function __construct(private AuditService $audit) {}

    /**
     * Stok dihitung, bukan disimpan: SUM(mutasi_stok.jumlah) (ARCHITECTURE §3.1 B3).
     */
    public function stok(Sparepart|int $sparepart): int
    {
        $id = $sparepart instanceof Sparepart ? $sparepart->id_sparepart : $sparepart;

        return (int) DB::table('mutasi_stok')->where('id_sparepart', $id)->sum('jumlah');
    }

    /**
     * Catat mutasi stok masuk (fitur 35: stok awal spare part baru).
     */
    public function catatMasuk(Sparepart $sparepart, int $jumlah, ?string $alasan, Pengguna $pelaku): MutasiStok
    {
        if ($jumlah <= 0) {
            throw ValidationException::withMessages(['jumlah' => 'Jumlah stok masuk harus lebih dari 0.']);
        }

        return DB::transaction(function () use ($sparepart, $jumlah, $alasan, $pelaku) {
            // Kunci baris sparepart sebelum menghitung stok (ARCHITECTURE §3.1 B3).
            $terkunci = Sparepart::query()->whereKey($sparepart->getKey())->lockForUpdate()->firstOrFail();

            $mutasi = MutasiStok::create([
                'id_sparepart' => $terkunci->id_sparepart,
                'jenis' => MutasiStok::JENIS_MASUK,
                'jumlah' => $jumlah,
                'alasan' => $alasan,
                'dibuat_oleh' => $pelaku->id_pengguna,
                'waktu' => now(),
            ]);

            $this->audit->catat('stok_masuk', $terkunci, null, [
                'id_mutasi' => $mutasi->id_mutasi,
                'jumlah' => $jumlah,
                'alasan' => $alasan,
                'stok' => $this->stok($terkunci),
            ], $pelaku);

            return $mutasi;
        });
    }

    /**
     * Penyesuaian stok (fitur 38): jumlah boleh negatif, alasan wajib, stok tidak
     * boleh negatif. Baris spare part dikunci sebelum stok dihitung.
     */
    public function catatPenyesuaian(Sparepart $sparepart, int $jumlah, string $alasan, Pengguna $pelaku): MutasiStok
    {
        if ($jumlah === 0) {
            throw ValidationException::withMessages(['jumlah' => 'Jumlah penyesuaian tidak boleh 0.']);
        }

        return DB::transaction(function () use ($sparepart, $jumlah, $alasan, $pelaku) {
            $terkunci = Sparepart::query()->whereKey($sparepart->getKey())->lockForUpdate()->firstOrFail();
            $stok = $this->stok($terkunci);

            if ($stok + $jumlah < 0) {
                throw ValidationException::withMessages([
                    'jumlah' => "Stok tidak boleh negatif. Stok sekarang {$stok}.",
                ]);
            }

            $mutasi = MutasiStok::create([
                'id_sparepart' => $terkunci->id_sparepart,
                'jenis' => MutasiStok::JENIS_PENYESUAIAN,
                'jumlah' => $jumlah,
                'alasan' => $alasan,
                'dibuat_oleh' => $pelaku->id_pengguna,
                'waktu' => now(),
            ]);

            $this->audit->catat('stok_penyesuaian', $terkunci, ['stok' => $stok], [
                'id_mutasi' => $mutasi->id_mutasi,
                'jumlah' => $jumlah,
                'alasan' => $alasan,
                'stok' => $this->stok($terkunci),
            ], $pelaku);

            return $mutasi;
        });
    }
}
