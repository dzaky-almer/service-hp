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

it('tamu diarahkan ke login dari halaman ber-auth', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('mengizinkan akun aktif membuka dashboard', function () {
    $this->actingAs(Pengguna::factory()->create())->get('/dashboard')->assertOk();
});

it('menolak akun nonaktif dan menunggu aktivasi di dashboard', function (string $status) {
    $this->actingAs(Pengguna::factory()->create(['status_akun' => $status]))
        ->get('/dashboard')
        ->assertForbidden();
})->with([
    'nonaktif' => Pengguna::STATUS_NONAKTIF,
    'menunggu aktivasi' => Pengguna::STATUS_MENUNGGU_AKTIVASI,
]);

it('menolak akun nonaktif di halaman profil', function () {
    $this->actingAs(Pengguna::factory()->create(['status_akun' => Pengguna::STATUS_NONAKTIF]))
        ->get('/profil')
        ->assertForbidden();
});

it('tetap mengizinkan akun nonaktif logout', function () {
    $this->actingAs(Pengguna::factory()->create(['status_akun' => Pengguna::STATUS_NONAKTIF]))
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
