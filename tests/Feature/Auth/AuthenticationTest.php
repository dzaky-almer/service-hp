<?php

use App\Models\Pengguna;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = Pengguna::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('login mengganti id session', function () {
    $user = Pengguna::factory()->create();

    $this->get('/login');
    $idSebelum = session()->getId();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    expect(session()->getId())->not->toBe($idSebelum);
});

test('users can not authenticate with invalid password', function () {
    $user = Pengguna::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('akun nonaktif dan menunggu aktivasi ditolak login dengan pesan netral', function (string $status) {
    $user = Pengguna::factory()->create(['status_akun' => $status]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => 'Email atau password salah.']);
})->with([
    'nonaktif' => Pengguna::STATUS_NONAKTIF,
    'menunggu aktivasi' => Pengguna::STATUS_MENUNGGU_AKTIVASI,
]);

test('pesan galat tidak membedakan email tidak ada dan password salah', function () {
    $user = Pengguna::factory()->create();

    $salahPassword = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $emailTidakAda = $this->from('/login')->post('/login', [
        'email' => 'tidak-ada@example.com',
        'password' => 'password',
    ]);

    $salahPassword->assertSessionHasErrors(['email' => 'Email atau password salah.']);
    $emailTidakAda->assertSessionHasErrors(['email' => 'Email atau password salah.']);
});

test('users can logout', function () {
    $user = Pengguna::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
