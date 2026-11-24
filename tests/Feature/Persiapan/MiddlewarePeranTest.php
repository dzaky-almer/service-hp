<?php

use App\Models\Pengguna;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'peran:admin,owner'])->get('/uji-peran', fn () => 'ok');
    Route::middleware(['web', 'akun.aktif'])->get('/uji-aktif', fn () => 'ok');
});

/** Pengguna sementara; kolom peran/status_akun ada di migrasi pengguna. */
function penggunaUji(?string $peran, string $status = 'aktif'): Pengguna
{
    $u = new Pengguna;
    $u->peran = $peran;
    $u->status_akun = $status;

    return $u;
}

it('menolak tamu di route berperan', function () {
    $this->get('/uji-peran')->assertForbidden();
});

it('mengizinkan peran terdaftar dan menolak peran lain', function () {
    $this->actingAs(penggunaUji('owner'))->get('/uji-peran')->assertOk();
    $this->actingAs(penggunaUji('customer'))->get('/uji-peran')->assertForbidden();
});

it('menolak akun nonaktif dan menunggu aktivasi', function () {
    $this->actingAs(penggunaUji('admin', 'nonaktif'))->get('/uji-aktif')->assertForbidden();
    $this->actingAs(penggunaUji('admin', 'menunggu_aktivasi'))->get('/uji-aktif')->assertForbidden();
    $this->actingAs(penggunaUji('admin'))->get('/uji-aktif')->assertOk();
});
