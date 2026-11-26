<?php

use App\Models\Pengguna;
use App\Models\UndanganAkun;
use App\Notifications\UndanganAktivasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

const PASSWORD_AKTIVASI = 'PasswordBaru123!';

/**
 * Undang satu staf lewat endpoint Owner lalu tangkap token asli dari email.
 *
 * @return array{Pengguna, string, Pengguna}
 */
function undangStafUji(object $test, array $ubah = []): array
{
    Notification::fake();

    $owner = Pengguna::factory()->create(['peran' => Pengguna::PERAN_OWNER]);

    $test->actingAs($owner)->post('/admin/staf', $ubah + [
        'nama' => 'Staf Uji Aktivasi',
        'email' => 'staf.aktivasi@servis.test',
        'no_hp' => '081200000021',
        'peran' => Pengguna::PERAN_TEKNISI,
    ]);

    $staf = Pengguna::firstWhere('email', 'staf.aktivasi@servis.test');

    $token = null;
    Notification::assertSentTo($staf, UndanganAktivasi::class, function (UndanganAktivasi $notifikasi) use (&$token) {
        $token = $notifikasi->token;

        return true;
    });

    Auth::logout();   // halaman aktivasi hanya untuk tamu

    return [$staf, $token, $owner];
}

test('token undangan yang sah menampilkan form aktivasi', function () {
    [$staf, $token] = undangStafUji($this);

    $this->get('/aktivasi/'.$token)
        ->assertOk()
        ->assertSee('Aktivasi akun')
        ->assertSee($staf->nama)
        ->assertSee($staf->email);
});

test('token yang tidak dikenal dialihkan ke login tanpa mengubah apa pun', function () {
    [$staf] = undangStafUji($this);

    $this->get('/aktivasi/'.str_repeat('a', 64))
        ->assertRedirect(route('login', absolute: false))
        ->assertSessionHasErrors('email');

    $this->post('/aktivasi/'.str_repeat('a', 64), [
        'password' => PASSWORD_AKTIVASI,
        'password_confirmation' => PASSWORD_AKTIVASI,
    ])->assertRedirect(route('login', absolute: false))
        ->assertSessionHasErrors('email');

    expect($staf->refresh()->status_akun)->toBe(Pengguna::STATUS_MENUNGGU_AKTIVASI)
        ->and($staf->password)->toBeNull()
        ->and(UndanganAkun::firstWhere('id_pengguna', $staf->id_pengguna)->dipakai_pada)->toBeNull();
});

test('aktivasi mengaktifkan akun dengan password dari pengguna sendiri', function () {
    [$staf, $token] = undangStafUji($this);
    auth()->logout();

    $this->post('/aktivasi/'.$token, [
        'password' => PASSWORD_AKTIVASI,
        'password_confirmation' => PASSWORD_AKTIVASI,
    ])->assertRedirect(route('dashboard', absolute: false))
        ->assertSessionHas('status');

    $staf->refresh();

    expect($staf->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and(Hash::check(PASSWORD_AKTIVASI, $staf->password))->toBeTrue()
        ->and(UndanganAkun::firstWhere('id_pengguna', $staf->id_pengguna)->dipakai_pada)->not->toBeNull();

    $this->assertAuthenticatedAs($staf);
});

test('token undangan hanya bisa dipakai sekali', function () {
    [$staf, $token] = undangStafUji($this);
    auth()->logout();

    $this->post('/aktivasi/'.$token, [
        'password' => PASSWORD_AKTIVASI,
        'password_confirmation' => PASSWORD_AKTIVASI,
    ])->assertRedirect(route('dashboard', absolute: false));

    auth()->logout();

    $this->get('/aktivasi/'.$token)->assertRedirect(route('login', absolute: false));

    $this->post('/aktivasi/'.$token, [
        'password' => 'PasswordKetiga456!',
        'password_confirmation' => 'PasswordKetiga456!',
    ])->assertRedirect(route('login', absolute: false));

    expect(Hash::check(PASSWORD_AKTIVASI, $staf->refresh()->password))->toBeTrue()
        ->and($staf->status_akun)->toBe(Pengguna::STATUS_AKTIF);
});

test('token yang lewat 72 jam ditolak dan akun tetap menunggu aktivasi', function () {
    [$staf, $token] = undangStafUji($this);
    auth()->logout();

    $undangan = UndanganAkun::firstWhere('id_pengguna', $staf->id_pengguna);
    $undangan->kedaluwarsa_pada = now()->subMinute();
    $undangan->save();

    $this->get('/aktivasi/'.$token)->assertRedirect(route('login', absolute: false));

    $this->post('/aktivasi/'.$token, [
        'password' => PASSWORD_AKTIVASI,
        'password_confirmation' => PASSWORD_AKTIVASI,
    ])->assertRedirect(route('login', absolute: false));

    expect($staf->refresh()->status_akun)->toBe(Pengguna::STATUS_MENUNGGU_AKTIVASI)
        ->and($staf->password)->toBeNull()
        ->and($undangan->refresh()->dipakai_pada)->toBeNull();
});

test('aktivasi menolak password lemah atau konfirmasi yang tidak cocok', function () {
    [$staf, $token] = undangStafUji($this);
    auth()->logout();

    $this->post('/aktivasi/'.$token, ['password' => 'abc', 'password_confirmation' => 'abc'])
        ->assertSessionHasErrors('password');

    $this->post('/aktivasi/'.$token, [
        'password' => PASSWORD_AKTIVASI,
        'password_confirmation' => 'PasswordBeda123!',
    ])->assertSessionHasErrors('password');

    expect($staf->refresh()->status_akun)->toBe(Pengguna::STATUS_MENUNGGU_AKTIVASI)
        ->and($staf->password)->toBeNull()
        ->and(UndanganAkun::firstWhere('id_pengguna', $staf->id_pengguna)->dipakai_pada)->toBeNull();
});

test('aktivasi menulis audit log dan akun hasil aktivasi bisa login', function () {
    [$staf, $token, $owner] = undangStafUji($this);
    auth()->logout();

    $this->post('/aktivasi/'.$token, [
        'password' => PASSWORD_AKTIVASI,
        'password_confirmation' => PASSWORD_AKTIVASI,
    ]);

    $this->assertDatabaseHas('audit_log', [
        'id_pengguna' => $staf->id_pengguna,
        'aksi' => 'aktivasi_akun',
        'objek_tipe' => 'Pengguna',
        'objek_id' => $staf->id_pengguna,
    ]);

    auth()->logout();

    $this->post('/login', ['email' => 'staf.aktivasi@servis.test', 'password' => PASSWORD_AKTIVASI])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($staf);
    expect($staf->id_pengguna)->not->toBe($owner->id_pengguna);
});
