<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sku', 'nama', 'harga_beli', 'harga_jual', 'stok_minimum'])]
class Sparepart extends Model
{
    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    /** Label tampilan status spare part. */
    public const LABEL_STATUS = [
        self::STATUS_AKTIF => 'Aktif',
        self::STATUS_NONAKTIF => 'Nonaktif',
    ];

    protected $table = 'sparepart';

    protected $primaryKey = 'id_sparepart';

    /** Tabel `sparepart` tidak punya kolom waktu. */
    public $timestamps = false;

    /** Semua mutasi stok; stok = SUM(jumlah), tidak ada kolom stok (ARCHITECTURE §3.1). */
    public function mutasi(): HasMany
    {
        return $this->hasMany(MutasiStok::class, 'id_sparepart', 'id_sparepart');
    }

    public function labelStatus(): string
    {
        return self::LABEL_STATUS[$this->status] ?? $this->status;
    }
}
