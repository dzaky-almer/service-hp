<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Catatan pengerjaan (fitur 31): riwayat tulisan teknisi/admin, tidak diubah/dihapus. */
#[Fillable(['id_servis', 'id_pengguna', 'isi', 'waktu'])]
class CatatanPengerjaan extends Model
{
    protected $table = 'catatan_pengerjaan';

    protected $primaryKey = 'id_catatan';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['waktu' => 'datetime'];
    }

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }
}
