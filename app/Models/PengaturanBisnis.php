<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['kunci', 'nilai'])]
class PengaturanBisnis extends Model
{
    protected $table = 'pengaturan_bisnis';

    protected $primaryKey = 'kunci';

    protected $keyType = 'string';

    public $incrementing = false;

    /** Tabel `pengaturan_bisnis` tidak punya kolom waktu. */
    public $timestamps = false;

    /**
     * Seluruh pengaturan sebagai pasangan kunci => nilai.
     *
     * @return array<string, string>
     */
    public static function semua(): array
    {
        return self::query()->orderBy('kunci')->pluck('nilai', 'kunci')->all();
    }

    public static function nilai(string $kunci, ?string $bawaan = null): ?string
    {
        return self::query()->whereKey($kunci)->value('nilai') ?? $bawaan;
    }
}
