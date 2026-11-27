<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_sparepart', 'jenis', 'jumlah', 'id_servis', 'alasan', 'dibuat_oleh', 'waktu'])]
class MutasiStok extends Model
{
    public const JENIS_MASUK = 'masuk';

    public const JENIS_KELUAR_SERVIS = 'keluar_servis';

    public const JENIS_PENYESUAIAN = 'penyesuaian';

    public const LABEL_JENIS = [
        self::JENIS_MASUK => 'Stok masuk',
        self::JENIS_KELUAR_SERVIS => 'Keluar untuk servis',
        self::JENIS_PENYESUAIAN => 'Penyesuaian',
    ];

    protected $table = 'mutasi_stok';

    protected $primaryKey = 'id_mutasi';

    /** Tabel append-only tanpa kolom waktu standar; waktu dicatat eksplisit. */
    public $timestamps = false;

    protected function casts(): array
    {
        return ['waktu' => 'datetime'];
    }

    public function sparepart(): BelongsTo
    {
        return $this->belongsTo(Sparepart::class, 'id_sparepart', 'id_sparepart');
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh', 'id_pengguna');
    }

    public function labelJenis(): string
    {
        return self::LABEL_JENIS[$this->jenis] ?? $this->jenis;
    }
}
