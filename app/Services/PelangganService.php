<?php

namespace App\Services;

use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Models\UndanganAkun;
use Illuminate\Support\Facades\DB;

class PelangganService
{
    public function __construct(private AuditService $audit, private UndanganService $undangan) {}

    /**
     * Buat atau ubah data pelanggan (fitur 11, 13). `status` tidak lewat mass
     * assignment: menonaktifkan pelanggan hanya lewat `nonaktifkan()`.
     *
     * @param  array<string, mixed>  $data
     */
    public function simpan(?Pelanggan $pelanggan, array $data, Pengguna $pelaku): Pelanggan
    {
        return DB::transaction(function () use ($pelanggan, $data, $pelaku): Pelanggan {
            $baru = $pelanggan === null;
            $pelanggan ??= new Pelanggan;
            $sebelum = $baru ? null : $pelanggan->only(['nama', 'no_hp', 'email', 'alamat']);

            $pelanggan->fill($data);
            $pelanggan->save();

            $this->audit->catat(
                $baru ? 'buat_pelanggan' : 'ubah_pelanggan',
                $pelanggan,
                $sebelum,
                ['nama' => $pelanggan->nama, 'no_hp' => $pelanggan->no_hp, 'email' => $pelanggan->email],
                $pelaku,
            );

            return $pelanggan;
        });
    }

    /**
     * Nonaktifkan pelanggan (fitur 14, BR-17: data master tidak dihapus permanen).
     * Akun yang tertaut ikut dinonaktifkan supaya pemiliknya tidak bisa login lagi.
     * Baris dikunci dan perubahan status yang sama diabaikan agar audit tidak ganda.
     */
    public function nonaktifkan(Pelanggan $pelanggan, Pengguna $pelaku): Pelanggan
    {
        return DB::transaction(function () use ($pelanggan, $pelaku): Pelanggan {
            $pelanggan = Pelanggan::whereKey($pelanggan->getKey())->lockForUpdate()->firstOrFail();

            if ($pelanggan->status === Pelanggan::STATUS_NONAKTIF) {
                return $pelanggan;
            }

            $pelanggan->status = Pelanggan::STATUS_NONAKTIF;
            $pelanggan->save();

            $akun = $pelanggan->pengguna;

            if ($akun !== null && $akun->status_akun === Pengguna::STATUS_AKTIF) {
                $akun->status_akun = Pengguna::STATUS_NONAKTIF;
                $akun->save();
            }

            $this->audit->catat(
                'nonaktifkan_pelanggan',
                $pelanggan,
                ['status' => Pelanggan::STATUS_AKTIF],
                ['status' => Pelanggan::STATUS_NONAKTIF],
                $pelaku,
            );

            return $pelanggan;
        });
    }

    /**
     * Kirim undangan akun untuk pelanggan toko yang belum punya akun (fitur 11).
     * Jalurnya sama dengan undangan staf: akun `menunggu_aktivasi` + token 72 jam
     * sekali pakai, lalu pelanggan ditautkan ke akun itu dalam transaksi yang sama.
     *
     * @return array{pengguna: Pengguna, undangan: UndanganAkun, token: string}
     */
    public function kirimUndanganAkun(Pelanggan $pelanggan, string $email, Pengguna $pelaku): array
    {
        return DB::transaction(function () use ($pelanggan, $email, $pelaku): array {
            $pelanggan = Pelanggan::whereKey($pelanggan->getKey())->lockForUpdate()->firstOrFail();

            $hasil = $this->undangan->undangAkun(
                $pelanggan->nama,
                $email,
                $pelanggan->no_hp,
                Pengguna::PERAN_CUSTOMER,
                $pelaku,
            );

            $pelanggan->id_pengguna = $hasil['pengguna']->id_pengguna;
            $pelanggan->email ??= $email;                       // email toko tetap dipakai bila sudah ada
            $pelanggan->save();

            $this->audit->catat('undang_akun_pelanggan', $pelanggan, null, [
                'email' => $email,
                'id_pengguna' => $pelanggan->id_pengguna,
            ], $pelaku);

            return $hasil;
        });
    }
}
