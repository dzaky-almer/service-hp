<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pemakaian spare part pada servis (fitur 37). Harga beli disalin saat dicatat
 * untuk laporan biaya; baris tidak diubah (BR-11).
 */
#[Fillable(['id_servis', 'id_sparepart', 'id_mutasi', 'jumlah', 'harga_beli_salinan', 'dicatat_oleh', 'waktu'])]
class PenggunaanSparepart extends Model
{
    protected $table = 'penggunaan_sparepart';

    protected $primaryKey = 'id_penggunaan';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['waktu' => 'datetime', 'harga_beli_salinan' => 'decimal:2', 'jumlah' => 'integer'];
    }

    public function sparepart(): BelongsTo
    {
        return $this->belongsTo(Sparepart::class, 'id_sparepart', 'id_sparepart');
    }

    public function mutasi(): BelongsTo
    {
        return $this->belongsTo(MutasiStok::class, 'id_mutasi', 'id_mutasi');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dicatat_oleh', 'id_pengguna');
    }

    public function hargaTotal(): float
    {
        return (float) $this->harga_beli_salinan * $this->jumlah;
    }
}
