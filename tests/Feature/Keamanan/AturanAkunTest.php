<?php

use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Services\UndanganService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/*
| Test lintas fitur Tahap 1 (identitas ganda, peran tidak dapat disetel, hash,
| akun nonaktif, akses lintas peran, token undangan, batas wewenang Admin).
| Semua pengecekan memakai rute dan tabel yang nyata, bukan rute uji.
*/

function dataDaftarAman(array $ubah = []): array
{
    return $ubah + [
        'nama' => 'Pelanggan Aman',
        'no_hp' => '081200009999',
        'email' => 'pelanggan.aman@example.com',
        'password' => 'rahasiaaman123',
        'password_confirmation' => 'rahasiaaman123',
    ];
}

function ownerAman(): Pengguna
{
    return Pengguna::factory()->create(['peran' => Pengguna::PERAN_OWNER]);
}

// --- Identitas ganda: penjaga terakhir ada di database, bukan hanya validasi ---

test('database menolak email dan no hp pengguna yang sama', function () {
    Pengguna::factory()->create(['email' => 'ganda@example.com', 'no_hp' => '081200000001']);

    expect(fn () => Pengguna::factory()->create(['email' => 'ganda@example.com']))
        ->toThrow(QueryException::class)
        ->and(fn () => Pengguna::factory()->create(['no_hp' => '081200000001']))
        ->toThrow(QueryException::class);

    expect(Pengguna::count())->toBe(1);
});

test('database menolak no hp pelanggan ganda dan dua pelanggan untuk satu akun', function () {
    $pengguna = Pengguna::factory()->create();
    Pelanggan::create(['nama' => 'Toko', 'no_hp' => '081200000002']);

    expect(fn () => Pelanggan::create(['nama' => 'Toko Lain', 'no_hp' => '081200000002']))
        ->toThrow(QueryException::class)
        ->and(fn () => Pelanggan::create(['nama' => 'A', 'id_pengguna' => $pengguna->id_pengguna]))
        ->toThrow(QueryException::class);

    expect(Pelanggan::count())->toBe(1);
});

// --- Peran dan status akun tidak dapat disetel dari input ---

test('registrasi mengabaikan peran dan status akun dari input', function () {
    $this->post('/register', dataDaftarAman([
        'peran' => Pengguna::PERAN_OWNER,
        'status_akun' => Pengguna::STATUS_NONAKTIF,
        'id_pengguna' => 1,
    ]))->assertRedirect(route('dashboard', absolute: false));

    $pengguna = Pengguna::firstWhere('email', 'pelanggan.aman@example.com');

    expect($pengguna->peran)->toBe(Pengguna::PERAN_CUSTOMER)
        ->and($pengguna->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and($pengguna->id_pengguna)->not->toBe(1);
});

test('ubah profil mengabaikan peran, status akun, dan password dari input', function () {
    $pengguna = Pengguna::factory()->create(['peran' => Pengguna::PERAN_CUSTOMER]);

    $this->actingAs($pengguna)->put('/profil', [
        'nama' => 'Nama Baru',
        'email' => 'profil.baru@example.com',
        'peran' => Pengguna::PERAN_OWNER,
        'status_akun' => Pengguna::STATUS_NONAKTIF,
        'password' => 'passwordpenyerang',
    ])->assertRedirect(route('profil.edit'));

    $pengguna->refresh();

    expect($pengguna->nama)->toBe('Nama Baru')
        ->and($pengguna->peran)->toBe(Pengguna::PERAN_CUSTOMER)
        ->and($pengguna->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and(Hash::check('password', $pengguna->password))->toBeTrue();
});

test('field peran pada permintaan status staf diabaikan', function () {
    $owner = ownerAman();
    $staf = Pengguna::factory()->create(['peran' => Pengguna::PERAN_ADMIN]);

    $this->actingAs($owner)->post(route('admin.staf.status', $staf), [
        'status_akun' => Pengguna::STATUS_NONAKTIF,
        'peran' => Pengguna::PERAN_OWNER,
    ])->assertRedirect();

    $staf->refresh();

    expect($staf->peran)->toBe(Pengguna::PERAN_ADMIN)
        ->and($staf->status_akun)->toBe(Pengguna::STATUS_NONAKTIF);
});

test('tidak ada rute yang dapat mengubah peran akun', function () {
    $owner = ownerAman();
    $staf = Pengguna::factory()->create(['peran' => Pengguna::PERAN_ADMIN]);

    $uris = collect(Route::getRoutes())->map(fn ($route) => $route->uri());

    expect($uris->filter(fn (string $uri) => str_contains($uri, 'peran')))->toBeEmpty();

    $this->actingAs($owner)
        ->post('/admin/staf/'.$staf->id_pengguna.'/peran', ['peran' => Pengguna::PERAN_OWNER])
        ->assertNotFound();

    expect($staf->refresh()->peran)->toBe(Pengguna::PERAN_ADMIN);
});

// --- Password dan token selalu tersimpan sebagai hash ---

test('password tersimpan sebagai hash bcrypt dan tidak pernah tampil di array model', function () {
    $this->post('/register', dataDaftarAman());

    $pengguna = Pengguna::firstWhere('email', 'pelanggan.aman@example.com');

    expect($pengguna->password)->not->toBe('rahasiaaman123')
        ->and(strlen($pengguna->password))->toBe(60)
        ->and($pengguna->password)->toStartWith('$2y$')
        ->and(Hash::check('rahasiaaman123', $pengguna->password))->toBeTrue()
        ->and(DB::table('pengguna')->where('password', 'rahasiaaman123')->exists())->toBeFalse()
        ->and(array_keys($pengguna->fresh()->toArray()))->not->toContain('password');
});

test('token undangan hanya tersimpan sebagai hash dan berbeda tiap undangan', function () {
    $owner = ownerAman();
    $undanganService = app(UndanganService::class);

    $pertama = $undanganService->undangStaf('Staf Satu', 'staf.satu@example.com', null, Pengguna::PERAN_TEKNISI, $owner);
    $kedua = $undanganService->undangStaf('Staf Dua', 'staf.dua@example.com', null, Pengguna::PERAN_ADMIN, $owner);

    $token = $pertama['token'];

    expect(strlen($token))->toBe(64)
        ->and(ctype_xdigit($token))->toBeTrue()
        ->and($token)->not->toBe($kedua['token'])
        ->and($pertama['undangan']->token_hash)->toBe(hash('sha256', $token))
        ->and($pertama['undangan']->token_hash)->not->toBe($kedua['undangan']->token_hash)
        ->and($pertama['undangan']->masihBerlaku())->toBeTrue()
        ->and($pertama['undangan']->kedaluwarsa_pada->timestamp)->toBe(now()->addHours(72)->timestamp);

    // Token mentah tidak boleh ada di tabel mana pun.
    expect(json_encode(DB::table('undangan_akun')->get()))->not->toContain($token)
        ->and(json_encode(DB::table('pengguna')->get()))->not->toContain($token)
        ->and(json_encode(DB::table('audit_log')->get()))->not->toContain($token);
});

// --- Akun nonaktif dan menunggu aktivasi tidak dapat memakai aplikasi ---

test('akun nonaktif dan menunggu aktivasi ditolak di seluruh halaman ber-auth', function (string $status) {
    $pengguna = Pengguna::factory()->create(['status_akun' => $status]);

    foreach (['/dashboard', '/profil'] as $halaman) {
        $this->actingAs($pengguna)->get($halaman)->assertForbidden();
    }

    $this->actingAs($pengguna)->put('/profil', ['nama' => 'X', 'email' => 'x@example.com'])->assertForbidden();
})->with([
    'nonaktif' => Pengguna::STATUS_NONAKTIF,
    'menunggu aktivasi' => Pengguna::STATUS_MENUNGGU_AKTIVASI,
]);

// --- Akses lintas peran pada rute nyata ---

test('peran selain Owner ditolak di seluruh area admin staf', function (string $peran) {
    $pelaku = Pengguna::factory()->create(['peran' => $peran]);
    $sasaran = Pengguna::factory()->create(['peran' => Pengguna::PERAN_TEKNISI]);

    $this->actingAs($pelaku)->get('/admin/staf')->assertForbidden();
    $this->actingAs($pelaku)->post('/admin/staf', [
        'nama' => 'Staf Baru',
        'email' => 'staf.baru@example.com',
        'peran' => Pengguna::PERAN_TEKNISI,
    ])->assertForbidden();
    $this->actingAs($pelaku)->post(route('admin.staf.status', $sasaran), ['status_akun' => Pengguna::STATUS_NONAKTIF])
        ->assertForbidden();

    expect(Pengguna::where('email', 'staf.baru@example.com')->exists())->toBeFalse()
        ->and($sasaran->refresh()->status_akun)->toBe(Pengguna::STATUS_AKTIF);
})->with([
    'customer' => Pengguna::PERAN_CUSTOMER,
    'teknisi' => Pengguna::PERAN_TEKNISI,
    'admin' => Pengguna::PERAN_ADMIN,
]);

test('Owner diizinkan di area admin staf dan tamu diarahkan ke login', function () {
    $owner = ownerAman();

    $this->actingAs($owner)->get('/admin/staf')->assertOk();
    $this->actingAs($owner)->post('/admin/staf', [
        'nama' => 'Staf Baru',
        'email' => 'staf.baru@example.com',
        'peran' => Pengguna::PERAN_TEKNISI,
    ])->assertRedirect();

    expect(Pengguna::where('email', 'staf.baru@example.com')->exists())->toBeTrue();

    Auth::logout();
    $this->get('/admin/staf')->assertRedirect('/login');
});

test('Admin tidak dapat mengubah peran sendiri maupun menonaktifkan Owner', function () {
    $admin = Pengguna::factory()->create(['peran' => Pengguna::PERAN_ADMIN]);
    $owner = ownerAman();

    $this->actingAs($admin)
        ->post(route('admin.staf.status', $admin), ['status_akun' => Pengguna::STATUS_NONAKTIF, 'peran' => 'owner'])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('admin.staf.status', $owner), ['status_akun' => Pengguna::STATUS_NONAKTIF])
        ->assertForbidden();

    expect($admin->refresh()->peran)->toBe(Pengguna::PERAN_ADMIN)
        ->and($admin->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and($owner->refresh()->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and(DB::table('audit_log')->count())->toBe(0);
});

test('Owner tidak dapat menonaktifkan akunnya sendiri atau akun Owner lain', function () {
    $owner = ownerAman();
    $ownerLain = ownerAman();

    foreach ([$owner, $ownerLain] as $sasaran) {
        $this->actingAs($owner)
            ->post(route('admin.staf.status', $sasaran), ['status_akun' => Pengguna::STATUS_NONAKTIF])
            ->assertForbidden();
    }

    expect($owner->refresh()->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and($ownerLain->refresh()->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and(DB::table('audit_log')->count())->toBe(0);
});
