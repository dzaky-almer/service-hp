<?php

use App\Models\Pengguna;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

const PESAN_NETRAL_LUPA_PASSWORD = 'Jika email tersebut terdaftar, kami sudah mengirim tautan reset password ke email itu.';
const PESAN_GAGAL_RESET = 'Tautan reset password tidak valid atau sudah kedaluwarsa.';

test('halaman lupa password dapat dibuka', function () {
    $this->get('/lupa-password')->assertStatus(200);
});

test('rute lama /forgot-password dan POST /reset-password sudah tidak ada', function () {
    $this->get('/forgot-password')->assertNotFound();
    $this->post('/reset-password')->assertNotFound();
});

test('email terdaftar menerima notifikasi tautan reset', function () {
    Notification::fake();

    $user = Pengguna::factory()->create();

    $this->post('/lupa-password', ['email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', PESAN_NETRAL_LUPA_PASSWORD);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('respons untuk email tidak terdaftar sama persis dengan email terdaftar', function () {
    Notification::fake();

    $user = Pengguna::factory()->create();

    $terdaftar = $this->from('/lupa-password')->post('/lupa-password', ['email' => $user->email]);
    $tidakTerdaftar = $this->from('/lupa-password')->post('/lupa-password', ['email' => 'tidak-ada@example.com']);

    $terdaftar->assertRedirect('/lupa-password')
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', PESAN_NETRAL_LUPA_PASSWORD);
    $tidakTerdaftar->assertRedirect('/lupa-password')
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', PESAN_NETRAL_LUPA_PASSWORD);

    Notification::assertSentToTimes($user, ResetPassword::class, 1);
    Notification::assertCount(1);
});

test('permintaan tautan berulang dalam 60 detik tetap dijawab netral tanpa notifikasi kedua', function () {
    Notification::fake();

    $user = Pengguna::factory()->create();

    $this->post('/lupa-password', ['email' => $user->email]);
    $kedua = $this->from('/lupa-password')->post('/lupa-password', ['email' => $user->email]);

    $kedua->assertSessionHasNoErrors()->assertSessionHas('status', PESAN_NETRAL_LUPA_PASSWORD);
    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});

test('halaman reset password dapat dibuka dengan token', function () {
    Notification::fake();

    $user = Pengguna::factory()->create();

    $this->post('/lupa-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $this->get('/reset-password/'.$notification->token)->assertStatus(200);

        return true;
    });
});

test('token hanya disimpan sebagai hash di database', function () {
    Notification::fake();

    $user = Pengguna::factory()->create();

    $this->post('/lupa-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $tokenHash = DB::table('password_reset_tokens')->value('token');

        expect($tokenHash)->not->toBe($notification->token)
            ->and(Hash::check($notification->token, $tokenHash))->toBeTrue();

        return true;
    });
});

test('password dapat direset dengan token valid dan langsung bisa dipakai login', function () {
    Notification::fake();

    $user = Pengguna::factory()->create();

    $this->post('/lupa-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->post('/reset-password/'.$notification->token, [
            'email' => $user->email,
            'password' => 'password-baru-rahasia',
            'password_confirmation' => 'password-baru-rahasia',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $user->refresh();

        expect(Hash::check('password-baru-rahasia', $user->password))->toBeTrue()
            ->and($user->password)->not->toBe('password-baru-rahasia');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-baru-rahasia',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);

        return true;
    });
});

test('token reset hanya dapat dipakai sekali', function () {
    Notification::fake();

    $user = Pengguna::factory()->create();

    $this->post('/lupa-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $data = [
            'email' => $user->email,
            'password' => 'password-baru-rahasia',
            'password_confirmation' => 'password-baru-rahasia',
        ];

        $this->post('/reset-password/'.$notification->token, $data)->assertSessionHasNoErrors();

        $this->post('/reset-password/'.$notification->token, $data)
            ->assertSessionHasErrors(['email' => PESAN_GAGAL_RESET]);

        expect(Hash::check('password-baru-rahasia', $user->refresh()->password))->toBeTrue();

        return true;
    });
});

test('token reset kedaluwarsa setelah 60 menit', function () {
    Notification::fake();

    $user = Pengguna::factory()->create();

    $this->post('/lupa-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        DB::table('password_reset_tokens')->update(['created_at' => now()->subMinutes(61)]);

        $this->post('/reset-password/'.$notification->token, [
            'email' => $user->email,
            'password' => 'password-baru-rahasia',
            'password_confirmation' => 'password-baru-rahasia',
        ])->assertSessionHasErrors(['email' => PESAN_GAGAL_RESET]);

        expect(Hash::check('password-baru-rahasia', $user->refresh()->password))->toBeFalse();

        return true;
    });
});

test('token acak yang tidak pernah dikirim ditolak dengan pesan netral', function () {
    $user = Pengguna::factory()->create();

    $this->from('/reset-password/token-palsu')->post('/reset-password/token-palsu', [
        'email' => $user->email,
        'password' => 'password-baru-rahasia',
        'password_confirmation' => 'password-baru-rahasia',
    ])->assertSessionHasErrors(['email' => PESAN_GAGAL_RESET]);

    $this->assertGuest();
});

test('email tidak terdaftar pada reset password ditolak dengan pesan yang sama', function () {
    $this->from('/reset-password/token-palsu')->post('/reset-password/token-palsu', [
        'email' => 'tidak-ada@example.com',
        'password' => 'password-baru-rahasia',
        'password_confirmation' => 'password-baru-rahasia',
    ])->assertSessionHasErrors(['email' => PESAN_GAGAL_RESET]);
});

test('password baru wajib dikonfirmasi dan memenuhi aturan minimal', function () {
    Notification::fake();

    $user = Pengguna::factory()->create();

    $this->post('/lupa-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->from('/reset-password/'.$notification->token)->post('/reset-password/'.$notification->token, [
            'email' => $user->email,
            'password' => 'password-baru-rahasia',
            'password_confirmation' => 'beda-password',
        ])->assertSessionHasErrors(['password']);

        $this->from('/reset-password/'.$notification->token)->post('/reset-password/'.$notification->token, [
            'email' => $user->email,
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors(['password']);

        expect(Hash::check('password', $user->refresh()->password))->toBeTrue();

        return true;
    });
});

test('email dan password wajib diisi pada reset password', function () {
    $this->from('/reset-password/token-palsu')->post('/reset-password/token-palsu', [])
        ->assertSessionHasErrors(['email', 'password']);
});
