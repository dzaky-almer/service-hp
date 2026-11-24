<?php

namespace App\Models;

use Database\Factories\PenggunaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nama', 'email', 'no_hp', 'password'])]
#[Hidden(['password'])]
class Pengguna extends Authenticatable
{
    /** @use HasFactory<PenggunaFactory> */
    use HasFactory, Notifiable;

    public const PERAN_CUSTOMER = 'customer';

    public const PERAN_TEKNISI = 'teknisi';

    public const PERAN_ADMIN = 'admin';

    public const PERAN_OWNER = 'owner';

    public const STATUS_MENUNGGU_AKTIVASI = 'menunggu_aktivasi';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    public const DIBUAT_PADA = 'dibuat_pada';

    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    /** Tabel `pengguna` tidak punya `remember_token`; cookie "ingat saya" dimatikan. */
    protected $rememberTokenName = '';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
