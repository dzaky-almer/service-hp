<?php

namespace App\Services;

use App\Models\Diagnosis;
use App\Models\Pengguna;
use App\Models\Servis;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Diagnosis teknisi (fitur 23). Koreksi = versi baru; baris lama tidak diubah
 * (BR-11). `kategori_kerusakan` selalu huruf kecil.
 */
class DiagnosisService
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * @param  array{kategori_kerusakan: string, hasil: string}  $data
     */
    public function catat(Servis $servis, array $data, Pengguna $pelaku): Diagnosis
    {
        return DB::transaction(function () use ($servis, $data, $pelaku): Diagnosis {
            $baris = Servis::whereKey($servis->id_servis)->lockForUpdate()->firstOrFail();

            if (! $baris->bolehDiagnosis()) {
                throw ValidationException::withMessages([
                    'hasil' => 'Diagnosis hanya dapat diisi saat servis menunggu diagnosis atau sedang dikerjakan.',
                ]);
            }

            $diagnosis = $baris->diagnosis()->create([
                'versi' => (int) $baris->diagnosis()->max('versi') + 1,
                'kategori_kerusakan' => mb_strtolower(trim($data['kategori_kerusakan'])),
                'hasil' => trim($data['hasil']),
                'dibuat_oleh' => $pelaku->id_pengguna,
                'waktu' => now(),
            ]);

            $this->audit->catat('catat_diagnosis', $baris, null, [
                'versi' => $diagnosis->versi,
                'kategori_kerusakan' => $diagnosis->kategori_kerusakan,
            ], $pelaku);

            return $diagnosis;
        });
    }
}
