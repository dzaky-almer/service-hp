<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\UndanganAkun;
use Illuminate\Support\Facades\DB;

class UndanganService
{
    public function __construct(private AuditService $audit) {}

    /**
     * Undang akun baru (fitur 5 dan 11, BR-01): buat `pengguna` berstatus
     * `menunggu_aktivasi` tanpa password, plus satu token undangan yang hanya
     * hash-nya disimpan (SECURITY.md §1). Token asli dikembalikan sekali agar
     * pemanggil dapat mengirimkannya ke penerima.
     *
     * Dipakai dua jalur: undangan staf oleh Owner (peran admin/teknisi) dan
     * undangan akun untuk pelanggan toko yang belum punya akun (peran customer).
     *
     * @return array{pengguna: Pengguna, undangan: UndanganAkun, token: string}
     */
    public function undangAkun(string $nama, string $email, ?string $noHp, string $peran, Pengguna $pembuat): array
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

            $this->audit->catat(
                $peran === Pengguna::PERAN_CUSTOMER ? 'undang_pelanggan' : 'undang_staf',
                $pengguna,
                null,
                ['peran' => $peran, 'email' => $email, 'id_undangan' => $undangan->id_undangan],
                $pembuat,
            );

            return ['pengguna' => $pengguna, 'undangan' => $undangan, 'token' => $token];
        });
    }

    /**
     * Cari undangan yang masih berlaku dari token asli (fitur 6). Token asli
     * hanya dibandingkan lewat hash-nya, jadi tidak ada token mentah di DB.
     *
     * @param  bool  $kunci  kunci baris (`FOR UPDATE`) saat dipakai di dalam transaksi
     */
    public function cariUndanganValid(string $token, bool $kunci = false): ?UndanganAkun
    {
        $undangan = UndanganAkun::query()
            ->where('token_hash', hash('sha256', $token))
            ->when($kunci, fn ($q) => $q->lockForUpdate())
            ->first();

        return $undangan?->masihBerlaku() ? $undangan : null;
    }

    /**
     * Aktifkan akun lewat token undangan (fitur 6): pengguna menetapkan password
     * sendiri, status jadi `aktif`, undangan ditandai terpakai sekali (SECURITY.md §1).
     * Mengembalikan `null` bila token tidak valid, sudah dipakai, atau kedaluwarsa.
     */
    public function aktifkanAkun(string $token, string $password): ?Pengguna
    {
        return DB::transaction(function () use ($token, $password): ?Pengguna {
            // Baris dikunci dulu supaya dua permintaan bersamaan tidak dua-duanya lolos.
            $undangan = $this->cariUndanganValid($token, kunci: true);

            if ($undangan === null) {
                return null;
            }

            $pengguna = $undangan->pengguna;
            $pengguna->password = $password;                    // cast `hashed` di model
            $pengguna->status_akun = Pengguna::STATUS_AKTIF;
            $pengguna->save();

            $undangan->dipakai_pada = now();
            $undangan->save();

            $this->audit->catat('aktivasi_akun', $pengguna, [
                'status_akun' => Pengguna::STATUS_MENUNGGU_AKTIVASI,
            ], [
                'status_akun' => Pengguna::STATUS_AKTIF,
                'id_undangan' => $undangan->id_undangan,
            ], $pengguna);

            return $pengguna;
        });
    }
}
