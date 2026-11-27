<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Estimasi biaya per versi (fitur 24–28). Hanya satu versi `disetujui` per servis;
 * versi `disetujui`/`dikirim` tidak dapat diedit — perubahan = versi baru (BR-06).
 *
 * ponytail: total dihitung di PHP dari item yang sudah dimuat; cukup untuk
 * puluhan item per estimasi. Pindah ke SUM() SQL kalau laporan butuh agregat massal.
 */
#[Fillable([
    'id_servis',
    'versi',
    'status',
    'dibuat_oleh',
    'divalidasi_oleh',
    'dikirim_pada',
    'diputuskan_oleh',
    'diputuskan_pada',
    'catatan_customer',
    'dibuat_pada',
])]
class Estimasi extends Model
{
    public const DRAF = 'draf';

    public const DIKIRIM = 'dikirim';

    public const DISETUJUI = 'disetujui';

    public const DITOLAK = 'ditolak';

    public const DIGANTIKAN = 'digantikan';

    public const LABEL_STATUS = [
        self::DRAF => 'Draf',
        self::DIKIRIM => 'Dikirim',
        self::DISETUJUI => 'Disetujui',
        self::DITOLAK => 'Ditolak',
        self::DIGANTIKAN => 'Digantikan',
    ];

    /** Status akhir: tidak berubah lagi. */
    public const STATUS_AKHIR = [self::DITOLAK, self::DIGANTIKAN];

    /** Status yang membekukan isi item. */
    public const TERKUNCI = [self::DIKIRIM, self::DISETUJUI, self::DITOLAK, self::DIGANTIKAN];

    protected $table = 'estimasi';

    protected $primaryKey = 'id_estimasi';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'divalidasi_oleh' => 'integer',
            'dikirim_pada' => 'datetime',
            'diputuskan_pada' => 'datetime',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function servis(): BelongsTo
    {
        return $this->belongsTo(Servis::class, 'id_servis', 'id_servis');
    }

    public function item(): HasMany
    {
        return $this->hasMany(ItemEstimasi::class, 'id_estimasi', 'id_estimasi')->orderBy('id_item');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh', 'id_pengguna');
    }

    public function pemutus(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diputuskan_oleh', 'id_pengguna');
    }

    /** Total = SUM(jumlah × harga snapshot). Dihitung server (BR-08). */
    public function total(): float
    {
        return (float) $this->item->sum(fn (ItemEstimasi $baris) => $baris->subtotal());
    }

    public function labelStatus(): string
    {
        return self::LABEL_STATUS[$this->status] ?? $this->status;
    }

    public function bisaDiubah(): bool
    {
        return $this->status === self::DRAF;
    }
}
