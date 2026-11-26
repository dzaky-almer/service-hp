<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

class StafService
{
    public function __construct(private AuditService $audit) {}

    /**
     * Aktifkan/nonaktifkan akun staf (fitur 7). `status_akun` diubah lewat
     * assignment langsung, bukan mass assignment (BR-01), dan perubahan ditulis
     * ke `audit_log` (SECURITY.md §7: undang/nonaktifkan staf). Baris dikunci
     * supaya dua permintaan bersamaan tidak saling menimpa tanpa jejak.
     *
     * Penjaga peran (hanya Owner, bukan akun Owner sendiri) ada di controller;
     * service ini menganggap pemanggilnya sudah berwenang.
     */
    public function ubahStatus(Pengguna $staf, string $statusBaru, Pengguna $pelaku): Pengguna
    {
        return DB::transaction(function () use ($staf, $statusBaru, $pelaku): Pengguna {
            $staf = Pengguna::whereKey($staf->getKey())->lockForUpdate()->firstOrFail();
            $statusLama = $staf->status_akun;

            if ($statusLama === $statusBaru) {
                return $staf;
            }

            $staf->status_akun = $statusBaru;
            $staf->save();

            $this->audit->catat(
                $statusBaru === Pengguna::STATUS_AKTIF ? 'aktifkan_staf' : 'nonaktifkan_staf',
                $staf,
                ['status_akun' => $statusLama],
                ['status_akun' => $statusBaru],
                $pelaku,
            );

            return $staf;
        });
    }

    /**
     * Buat akun Owner (SECURITY.md §1): bukan lewat halaman publik dan bukan
     * lewat undangan, langsung berstatus `aktif` dengan password yang
     * ditetapkan pemanggil. Dipakai command `owner:buat`.
     */
    public function buatOwner(string $nama, string $email, string $password): Pengguna
    {
        return DB::transaction(function () use ($nama, $email, $password): Pengguna {
            $pengguna = new Pengguna(['nama' => $nama, 'email' => $email]);
            $pengguna->peran = Pengguna::PERAN_OWNER;
            $pengguna->status_akun = Pengguna::STATUS_AKTIF;
            $pengguna->password = $password;                     // cast `hashed` di model
            $pengguna->save();

            // Pelaku null: dijalankan dari CLI, bukan oleh pengguna yang login.
            $this->audit->catat('buat_owner', $pengguna, null, [
                'peran' => Pengguna::PERAN_OWNER,
                'email' => $email,
                'status_akun' => Pengguna::STATUS_AKTIF,
            ]);

            return $pengguna;
        });
    }
}
