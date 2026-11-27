<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama', 'harga'])]
class TarifJasa extends Model
{
    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    public const LABEL_STATUS = [
        self::STATUS_AKTIF => 'Aktif',
        self::STATUS_NONAKTIF => 'Nonaktif',
    ];

    protected $table = 'tarif_jasa';

    protected $primaryKey = 'id_tarif';

    /** Tabel `tarif_jasa` tidak punya kolom waktu. */
    public $timestamps = false;

    public function labelStatus(): string
    {
        return self::LABEL_STATUS[$this->status] ?? $this->status;
    }
}
