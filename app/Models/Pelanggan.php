<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_pengguna', 'nama', 'no_hp', 'email', 'alamat'])]
class Pelanggan extends Model
{
    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    /** Label tampilan status pelanggan. */
    public const LABEL_STATUS = [
        self::STATUS_AKTIF => 'Aktif',
        self::STATUS_NONAKTIF => 'Nonaktif',
    ];

    protected $table = 'pelanggan';

    protected $primaryKey = 'id_pelanggan';

    const CREATED_AT = 'dibuat_pada';

    /** Tabel `pelanggan` tidak punya `diperbarui_pada`. */
    const UPDATED_AT = null;

    /** Akun milik pelanggan; null bila pelanggan ditangani toko tanpa akun. */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function labelStatus(): string
    {
        return self::LABEL_STATUS[$this->status] ?? $this->status;
    }
}
