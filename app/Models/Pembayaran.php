<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catatan uang masuk/keluar untuk satu servis (fitur 39–43).
 *
 * `status` hanya berubah lewat `PembayaranService` atau webhook (ARCHITECTURE §3.3).
 * Koreksi tidak mengedit baris: dibuat baris baru `jenis = pembalikan` (BR-11).
 */
#[Fillable([
    'id_servis',
    'jenis',
    'id_pembayaran_asal',
    'metode',
    'nominal',
    'status',
    'id_transaksi_eksternal',
    'bukti_path',
    'dibuat_oleh',
    'dikonfirmasi_oleh',
    'alasan',
    'dibuat_pada',
    'diputuskan_pada',
])]
class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $primaryKey = 'id_pembayaran';

    public $timestamps = false;

    public const BAYAR = 'bayar';

    public const PEMBALIKAN = 'pembalikan';

    public const METODE = [
        'tunai' => 'Tunai',
        'transfer' => 'Transfer',
        'gateway' => 'Gateway',
    ];

    public const MENUNGGU = 'menunggu';

    public const BERHASIL = 'berhasil';

    public const GAGAL = 'gagal';

    public const LABEL_STATUS = [
        self::MENUNGGU => 'Menunggu',
        self::BERHASIL => 'Berhasil',
        self::GAGAL => 'Gagal',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'dibuat_pada' => 'datetime',
            'diputuskan_pada' => 'datetime',
        ];
    }

    public function servis(): BelongsTo
    {
        return $this->belongsTo(Servis::class, 'id_servis', 'id_servis');
    }

    public function asal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_pembayaran_asal', 'id_pembayaran');
    }

    /** Pembalikan yang mengacu baris ini. */
    public function pembalik(): HasMany
    {
        return $this->hasMany(self::class, 'id_pembayaran_asal', 'id_pembayaran');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh', 'id_pengguna');
    }

    public function dikonfirmasiOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dikonfirmasi_oleh', 'id_pengguna');
    }

    /** Efek bersih baris ini terhadap total dibayar. */
    public function efek(): float
    {
        if ($this->status !== self::BERHASIL) {
            return 0.0;
        }

        return $this->jenis === self::BAYAR ? (float) $this->nominal : -(float) $this->nominal;
    }

    /** Sisa nominal asal yang masih boleh dibalik. */
    public function sisaDapatDibalik(): float
    {
        return (float) $this->nominal - (float) $this->pembalik()->where('status', self::BERHASIL)->sum('nominal');
    }

    public function labelMetode(): string
    {
        return self::METODE[$this->metode] ?? $this->metode;
    }
}
