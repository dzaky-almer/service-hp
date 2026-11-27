<?php

use App\Models\Pengguna;

it('pesan galat validasi tampil dalam Bahasa Indonesia di form', function () {
    // Registrasi: password lemah.
    $this->post('/register', [
        'name' => 'Budi',
        'email' => 'budi@contoh.test',
        'password' => '123',
        'password_confirmation' => '123',
    ])->assertSessionHasErrors('password');

    expect(session('errors')->get('password')[0])
        ->toContain('password')->toContain('karakter');

    // Admin membuat pelanggan tanpa isian wajib.
    $admin = Pengguna::factory()->create();
    $admin->forceFill(['peran' => Pengguna::PERAN_ADMIN, 'status_akun' => Pengguna::STATUS_AKTIF])->save();

    $res = $this->actingAs($admin)->post(route('admin.pelanggan.store'), []);
    $res->assertSessionHasErrors(['nama', 'no_hp']);

    expect(session('errors')->get('nama')[0])->toBe('nama wajib diisi.')
        ->and(session('errors')->get('no_hp')[0])->toBe('no. HP wajib diisi.');

    // Tampilan banner galat di layout diuji lewat browser (uji HTTP asli):
    // header "Periksa kembali isian berikut:" + daftar pesan tampil di halaman.
});
