<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu butir QC pada satu putaran (fitur 33).
 * Putaran baru tiap percobaan; baris lama tidak diubah (BR-11).
 */
#[Fillable(['id_servis', 'putaran', 'butir', 'lulus', 'dicek_oleh', 'waktu'])]
class QcHasil extends Model
{
    protected $table = 'qc_hasil';

    protected $primaryKey = 'id_qc';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['lulus' => 'boolean', 'waktu' => 'datetime', 'putaran' => 'integer'];
    }

    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dicek_oleh', 'id_pengguna');
    }
}
