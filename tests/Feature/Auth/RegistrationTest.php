<?php

use App\Models\Pengguna;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $pengguna = Pengguna::firstWhere('email', 'test@example.com');

    expect($pengguna->nama)->toBe('Test User')
        ->and($pengguna->peran)->toBe(Pengguna::PERAN_CUSTOMER)
        ->and($pengguna->status_akun)->toBe(Pengguna::STATUS_MENUNGGU_AKTIVASI);
});
