<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris biaya dalam estimasi. `harga_satuan_salinan` adalah snapshot harga
 * master saat item dibuat (BR: perubahan harga master tidak mengubah item lama).
 */
#[Fillable(['id_estimasi', 'jenis', 'deskripsi', 'id_sparepart', 'id_tarif', 'jumlah', 'harga_satuan_salinan'])]
class ItemEstimasi extends Model
{
    public const JASA = 'jasa';

    public const SPAREPART = 'sparepart';

    protected $table = 'item_estimasi';

    protected $primaryKey = 'id_item';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'harga_satuan_salinan' => 'decimal:2',
        ];
    }

    public function estimasi(): BelongsTo
    {
        return $this->belongsTo(Estimasi::class, 'id_estimasi', 'id_estimasi');
    }

    public function sparepart(): BelongsTo
    {
        return $this->belongsTo(Sparepart::class, 'id_sparepart', 'id_sparepart');
    }

    public function tarif(): BelongsTo
    {
        return $this->belongsTo(TarifJasa::class, 'id_tarif', 'id_tarif');
    }

    public function subtotal(): float
    {
        return $this->jumlah * (float) $this->harga_satuan_salinan;
    }
}
