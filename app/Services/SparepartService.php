<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\Sparepart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SparepartService
{
    private const KOLOM_AUDIT = ['sku', 'nama', 'harga_beli', 'harga_jual', 'stok_minimum', 'status'];

    public function __construct(
        private AuditService $audit,
        private StokService $stok,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpan(?Sparepart $sparepart, array $data, Pengguna $pelaku): Sparepart
    {
        return DB::transaction(function () use ($sparepart, $data, $pelaku) {
            $baru = $sparepart === null;
            $sebelum = $sparepart?->only(self::KOLOM_AUDIT);
            $stokAwal = (int) ($data['stok_awal'] ?? 0);
            unset($data['stok_awal']);

            $sparepart ??= new Sparepart;
            $sparepart->fill($data);
            // Status tidak mass-assignable (pola BR-01): dijatuhkan sendiri.
            $sparepart->status = $data['status'] ?? $sparepart->status ?? Sparepart::STATUS_AKTIF;
            $sparepart->save();

            if ($baru && $stokAwal > 0) {
                $this->stok->catatMasuk($sparepart, $stokAwal, 'Stok awal', $pelaku);
            }

            $this->audit->catat(
                $baru ? 'buat_sparepart' : 'ubah_sparepart',
                $sparepart,
                $sebelum,
                $sparepart->only(self::KOLOM_AUDIT),
                $pelaku,
            );

            return $sparepart;
        });
    }

    /**
     * Spare part yang pernah dipakai tidak boleh hilang: nonaktifkan saja (TASK Tahap 2).
     */
    public function hapus(Sparepart $sparepart, Pengguna $pelaku): void
    {
        if ($this->sudahDipakai($sparepart)) {
            throw ValidationException::withMessages([
                'sparepart' => 'Spare part sudah pernah dipakai. Nonaktifkan saja, tidak boleh dihapus.',
            ]);
        }

        $sebelum = $sparepart->only(self::KOLOM_AUDIT);

        DB::transaction(function () use ($sparepart, $sebelum, $pelaku) {
            $sparepart->delete();
            // Model hasil delete masih membawa primary key untuk objek_id audit.
            $this->audit->catat('hapus_sparepart', $sparepart, $sebelum, null, $pelaku);
        });
    }

    public function sudahDipakai(Sparepart $sparepart): bool
    {
        return $sparepart->mutasi()->exists()
            || DB::table('item_estimasi')->where('id_sparepart', $sparepart->id_sparepart)->exists();
    }
}
