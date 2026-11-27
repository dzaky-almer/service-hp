<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bukti serah terima barang (fitur 44, 45). Satu baris per servis. */
#[Fillable(['id_servis', 'diserahkan_oleh', 'nama_penerima', 'metode_verifikasi', 'catatan', 'waktu'])]
class Pengambilan extends Model
{
    protected $table = 'pengambilan';

    protected $primaryKey = 'id_pengambilan';

    public $timestamps = false;

    public const METODE = [
        'kode_tracking' => 'Kode tracking',
        'identitas' => 'Identitas',
    ];

    protected function casts(): array
    {
        return ['waktu' => 'datetime'];
    }

    public function servis(): BelongsTo
    {
        return $this->belongsTo(Servis::class, 'id_servis', 'id_servis');
    }

    public function penyerah(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diserahkan_oleh', 'id_pengguna');
    }

    public function labelVerifikasi(): string
    {
        return self::METODE[$this->metode_verifikasi] ?? $this->metode_verifikasi;
    }
}
