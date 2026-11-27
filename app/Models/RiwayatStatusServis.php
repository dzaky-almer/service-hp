<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris per perpindahan status servis. Tidak pernah diubah/dihapus (BR-11).
 */
#[Fillable(['id_servis', 'dari_status', 'ke_status', 'diubah_oleh', 'alasan', 'waktu'])]
class RiwayatStatusServis extends Model
{
    protected $table = 'riwayat_status_servis';

    protected $primaryKey = 'id_riwayat';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['waktu' => 'datetime'];
    }

    public function servis(): BelongsTo
    {
        return $this->belongsTo(Servis::class, 'id_servis', 'id_servis');
    }

    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diubah_oleh', 'id_pengguna');
    }

    public function labelDari(): string
    {
        return $this->dari_status === null
            ? 'Barang masuk'
            : (Servis::LABEL_STATUS[$this->dari_status] ?? $this->dari_status);
    }

    public function labelKe(): string
    {
        return Servis::LABEL_STATUS[$this->ke_status] ?? $this->ke_status;
    }
}
