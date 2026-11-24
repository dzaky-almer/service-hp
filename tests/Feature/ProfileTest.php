<?php

use App\Models\Pengguna;

test('profile page is displayed', function () {
    $user = Pengguna::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = Pengguna::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'nama' => 'Test User',
            'no_hp' => '081234567890',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->nama);
    $this->assertSame('081234567890', $user->no_hp);
    $this->assertSame('test@example.com', $user->email);
});

test('user can delete their account', function () {
    $user = Pengguna::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
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
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
