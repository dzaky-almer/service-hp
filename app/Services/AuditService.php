<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditService
{
    /**
     * Catat aksi sensitif ke `audit_log` (SECURITY.md §7). Dipanggil dari Service
     * yang bersangkutan, bukan dari controller.
     *
     * @param  array<string, mixed>|null  $dataSebelum
     * @param  array<string, mixed>|null  $dataSesudah
     */
    public function catat(
        string $aksi,
        Model $objek,
        ?array $dataSebelum = null,
        ?array $dataSesudah = null,
        ?Pengguna $pelaku = null,
    ): void {
        DB::table('audit_log')->insert([
            'id_pengguna' => ($pelaku ?? Auth::user())?->id_pengguna,
            'aksi' => $aksi,
            'objek_tipe' => class_basename($objek),
            'objek_id' => $objek->getKey(),
            'data_sebelum' => $dataSebelum === null ? null : json_encode($dataSebelum),
            'data_sesudah' => $dataSesudah === null ? null : json_encode($dataSesudah),
            'ip' => request()->ip(),
            'waktu' => now(),
        ]);
    }
}
