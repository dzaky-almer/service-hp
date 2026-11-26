<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UndanganAkun extends Model
{
    /** ASUMSI #14 PRD: masa berlaku undangan akun. */
    public const MASA_BERLAKU_JAM = 72;

    protected $table = 'undangan_akun';

    protected $primaryKey = 'id_undangan';

    /** Tabel `undangan_akun` tidak punya kolom timestamp. */
    public $timestamps = false;

    protected $fillable = [
        'id_pengguna',
        'token_hash',
        'kedaluwarsa_pada',
        'dipakai_pada',
        'dibuat_oleh',
    ];

    /**
     * Pemilik undangan.
     *
     * @return BelongsTo<Pengguna, $this>
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    /** Undangan hanya berlaku sekali pakai dan sampai masa berlakunya lewat. */
    public function masihBerlaku(): bool
    {
        return $this->dipakai_pada === null && $this->kedaluwarsa_pada->isFuture();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kedaluwarsa_pada' => 'datetime',
            'dipakai_pada' => 'datetime',
        ];
    }
}
