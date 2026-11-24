<?php

use App\Models\Pelanggan;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;

function dataRegistrasi(array $ubah = []): array
{
    return $ubah + [
        'nama' => 'Test User',
        'no_hp' => '081234567890',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];
}

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registrasi customer membuat pengguna aktif dan pelanggan tertaut', function () {
    $response = $this->post('/register', dataRegistrasi());

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $pengguna = Pengguna::firstWhere('email', 'test@example.com');

    expect($pengguna->nama)->toBe('Test User')
        ->and($pengguna->no_hp)->toBe('081234567890')
        ->and($pengguna->peran)->toBe(Pengguna::PERAN_CUSTOMER)
        ->and($pengguna->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and(Hash::check('password', $pengguna->password))->toBeTrue()
        ->and($pengguna->password)->not->toBe('password');

    $pelanggan = Pelanggan::firstWhere('no_hp', '081234567890');

    expect($pelanggan)->not->toBeNull()
        ->and($pelanggan->id_pengguna)->toBe($pengguna->id_pengguna)
        ->and($pelanggan->nama)->toBe('Test User')
        ->and($pelanggan->email)->toBe('test@example.com');
});

test('registrasi menolak email ganda', function () {
    Pengguna::factory()->create(['email' => 'test@example.com']);

    $this->post('/register', dataRegistrasi())
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(Pengguna::where('email', 'test@example.com')->count())->toBe(1);
});

test('registrasi menolak no hp ganda pada pengguna', function () {
    Pengguna::factory()->create(['no_hp' => '081234567890']);

    $this->post('/register', dataRegistrasi())
        ->assertSessionHasErrors('no_hp');

    $this->assertGuest();
    expect(Pengguna::count())->toBe(1);
});

test('registrasi menolak no hp milik pelanggan tanpa akun dengan pesan hubungi toko', function () {
    Pelanggan::create([
        'nama' => 'Pelanggan Toko',
        'no_hp' => '081234567890',
    ]);

    $this->post('/register', dataRegistrasi())
        ->assertSessionHasErrors(['no_hp' => 'No. HP ini milik pelanggan toko yang belum punya akun. Hubungi toko untuk aktivasi.']);

    $this->assertGuest();
    expect(Pengguna::count())->toBe(0);
});

test('registrasi mengabaikan peran dan status akun dari input', function () {
    $this->post('/register', dataRegistrasi([
        'peran' => Pengguna::PERAN_OWNER,
        'status_akun' => Pengguna::STATUS_NONAKTIF,
    ]));

    $pengguna = Pengguna::firstWhere('email', 'test@example.com');

    expect($pengguna->peran)->toBe(Pengguna::PERAN_CUSTOMER)
        ->and($pengguna->status_akun)->toBe(Pengguna::STATUS_AKTIF);
});
