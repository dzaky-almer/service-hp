<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\TarifJasa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TarifJasaService
{
    private const KOLOM_AUDIT = ['nama', 'harga', 'status'];

    public function __construct(private AuditService $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpan(?TarifJasa $tarif, array $data, Pengguna $pelaku): TarifJasa
    {
        return DB::transaction(function () use ($tarif, $data, $pelaku) {
            $baru = $tarif === null;
            $sebelum = $tarif?->only(self::KOLOM_AUDIT);

            $tarif ??= new TarifJasa;
            $tarif->fill($data);
            $tarif->status = $data['status'] ?? $tarif->status ?? TarifJasa::STATUS_AKTIF;
            $tarif->save();

            $this->audit->catat(
                $baru ? 'buat_tarif_jasa' : 'ubah_tarif_jasa',
                $tarif,
                $sebelum,
                $tarif->only(self::KOLOM_AUDIT),
                $pelaku,
            );

            return $tarif;
        });
    }

    /**
     * Tarif yang sudah dipakai item estimasi tidak dihapus (TASK Tahap 2).
     */
    public function hapus(TarifJasa $tarif, Pengguna $pelaku): void
    {
        if (DB::table('item_estimasi')->where('id_tarif', $tarif->id_tarif)->exists()) {
            throw ValidationException::withMessages([
                'tarif' => 'Tarif jasa sudah dipakai. Nonaktifkan saja, tidak boleh dihapus.',
            ]);
        }

        $sebelum = $tarif->only(self::KOLOM_AUDIT);

        DB::transaction(function () use ($tarif, $sebelum, $pelaku) {
            $tarif->delete();
            $this->audit->catat('hapus_tarif_jasa', $tarif, $sebelum, null, $pelaku);
        });
    }
}
