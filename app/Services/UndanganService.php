<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\UndanganAkun;
use Illuminate\Support\Facades\DB;

class UndanganService
{
    public function __construct(private AuditService $audit) {}

    /**
     * Undang staf baru (fitur 5, BR-01): buat `pengguna` berstatus
     * `menunggu_aktivasi` tanpa password, plus satu token undangan yang hanya
     * hash-nya disimpan (SECURITY.md §1). Token asli dikembalikan sekali agar
     * pemanggil dapat mengirimkannya ke penerima.
     *
     * @return array{pengguna: Pengguna, undangan: UndanganAkun, token: string}
     */
    public function undangStaf(string $nama, string $email, ?string $noHp, string $peran, Pengguna $pembuat): array
    {
        return DB::transaction(function () use ($nama, $email, $noHp, $peran, $pembuat): array {
            $pengguna = new Pengguna(['nama' => $nama, 'email' => $email, 'no_hp' => $noHp]);
            $pengguna->peran = $peran;                          // peran tidak lewat mass assignment
            $pengguna->status_akun = Pengguna::STATUS_MENUNGGU_AKTIVASI;
            $pengguna->save();

            $token = bin2hex(random_bytes(32));

            $undangan = UndanganAkun::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'token_hash' => hash('sha256', $token),
                'kedaluwarsa_pada' => now()->addHours(UndanganAkun::MASA_BERLAKU_JAM),
                'dibuat_oleh' => $pembuat->id_pengguna,
            ]);

            $this->audit->catat('undang_staf', $pengguna, null, [
                'peran' => $peran,
                'email' => $email,
                'id_undangan' => $undangan->id_undangan,
            ], $pembuat);

            return ['pengguna' => $pengguna, 'undangan' => $undangan, 'token' => $token];
        });
    }
}
