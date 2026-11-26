<?php

use App\Models\Pelanggan;
use App\Models\Pengguna;

test('profile page is displayed', function () {
    $user = Pengguna::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profil');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = Pengguna::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/profil', [
            'nama' => 'Test User',
            'no_hp' => '081234567890',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profil');

    $user->refresh();

    $this->assertSame('Test User', $user->nama);
    $this->assertSame('081234567890', $user->no_hp);
    $this->assertSame('test@example.com', $user->email);
});

test('profile update syncs linked pelanggan row', function () {
    $user = Pengguna::factory()->create();
    $pelanggan = Pelanggan::create([
        'id_pengguna' => $user->id_pengguna,
        'nama' => $user->nama,
        'no_hp' => $user->no_hp,
        'email' => $user->email,
    ]);

    $this->actingAs($user)->put('/profil', [
        'nama' => 'Nama Baru',
        'no_hp' => '081200000001',
        'email' => 'baru@example.com',
    ])->assertSessionHasNoErrors();

    $pelanggan->refresh();

    $this->assertSame('Nama Baru', $pelanggan->nama);
    $this->assertSame('081200000001', $pelanggan->no_hp);
    $this->assertSame('baru@example.com', $pelanggan->email);
});

test('profile update fails when no hp belongs to another pengguna', function () {
    $user = Pengguna::factory()->create();
    $lain = Pengguna::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profil')
        ->put('/profil', [
            'nama' => $user->nama,
            'no_hp' => $lain->no_hp,
            'email' => $user->email,
        ]);

    $response->assertSessionHasErrors(['no_hp' => 'No. HP sudah terdaftar.']);
});

test('profile update fails when no hp belongs to pelanggan without account', function () {
    $user = Pengguna::factory()->create();
    Pelanggan::create([
        'nama' => 'Pelanggan Walk-in',
        'no_hp' => '081200000002',
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profil')
        ->put('/profil', [
            'nama' => $user->nama,
            'no_hp' => '081200000002',
            'email' => $user->email,
        ]);

    $response->assertSessionHasErrors(['no_hp' => 'No. HP sudah terdaftar.']);
});

test('profile update fails when email belongs to another pengguna', function () {
    $user = Pengguna::factory()->create();
    $lain = Pengguna::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profil')
        ->put('/profil', [
            'nama' => $user->nama,
            'no_hp' => $user->no_hp,
            'email' => $lain->email,
        ]);

    $response->assertSessionHasErrors('email');
});

test('profile update validates required fields', function () {
    $user = Pengguna::factory()->create();

    $this->actingAs($user)
        ->from('/profil')
        ->put('/profil', ['nama' => '', 'email' => ''])
        ->assertSessionHasErrors(['nama', 'email']);
});

test('user can delete their account', function () {
    $user = Pengguna::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profil', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = Pengguna::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profil')
        ->delete('/profil', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password');

    $this->assertNotNull($user->fresh());
});
