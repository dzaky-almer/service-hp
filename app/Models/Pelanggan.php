<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id_pengguna', 'nama', 'no_hp', 'email', 'alamat'])]
class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected $primaryKey = 'id_pelanggan';

    const CREATED_AT = 'dibuat_pada';

    /** Tabel `pelanggan` tidak punya `diperbarui_pada`. */
    const UPDATED_AT = null;
}
