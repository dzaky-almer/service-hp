<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hasil pemeriksaan teknisi (fitur 23). Koreksi = baris baru, versi naik
 * (ARCHITECTURE §3.3: tabel append-only, tidak ada jalur ubah/hapus).
 */
#[Fillable(['id_servis', 'versi', 'kategori_kerusakan', 'hasil', 'dibuat_oleh', 'waktu'])]
class Diagnosis extends Model
{
    protected $table = 'diagnosis';

    protected $primaryKey = 'id_diagnosis';

    /** Tabel `diagnosis` hanya punya kolom `waktu`. */
    public $timestamps = false;

    protected function casts(): array
    {
        return ['waktu' => 'datetime'];
    }

    public function servis(): BelongsTo
    {
        return $this->belongsTo(Servis::class, 'id_servis', 'id_servis');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh', 'id_pengguna');
    }
}
