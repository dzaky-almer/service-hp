<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Foto perangkat (`masuk`) atau hasil pengerjaan (`hasil`).
 *
 * `path_file` menunjuk disk privat `local`; tidak pernah disajikan langsung dari `public/`.
 */
#[Fillable(['id_servis', 'jenis', 'path_file', 'diunggah_oleh', 'waktu'])]
class FotoServis extends Model
{
    protected $table = 'foto_servis';

    protected $primaryKey = 'id_foto';

    public $timestamps = false;

    public const JENIS_MASUK = 'masuk';

    public const JENIS_HASIL = 'hasil';

    public const LABEL_JENIS = [
        self::JENIS_MASUK => 'Foto masuk',
        self::JENIS_HASIL => 'Foto hasil',
    ];

    protected function casts(): array
    {
        return ['waktu' => 'datetime'];
    }

    public function servis(): BelongsTo
    {
        return $this->belongsTo(Servis::class, 'id_servis', 'id_servis');
    }

    public function diunggahOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diunggah_oleh', 'id_pengguna');
    }

    public function labelJenis(): string
    {
        return self::LABEL_JENIS[$this->jenis] ?? $this->jenis;
    }
}
