<?php

use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Models\UndanganAkun;
use App\Notifications\UndanganAktivasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/*
| Tahap 2 — master data pelanggan (fitur 11–14): tambah/undang akun, daftar +
| pencarian, ubah, nonaktifkan. Semua memakai rute dan tabel nyata.
*/

function dataPelangganUji(array $ubah = []): array
{
    return $ubah + [
        'nama' => 'Budi Bengkel',
        'no_hp' => '081300000001',
        'email' => 'budi@example.com',
        'alamat' => 'Jl. Merdeka 1',
    ];
}

function adminPelangganUji(): Pengguna
{
    return Pengguna::factory()->create(['peran' => Pengguna::PERAN_ADMIN]);
}

function ownerPelangganUji(): Pengguna
{
    return Pengguna::factory()->create(['peran' => Pengguna::PERAN_OWNER]);
}

function aksiAudit(string $aksi): int
{
    return DB::table('audit_log')->where('aksi', $aksi)->count();
}

// --- Fitur 11: buat pelanggan, opsional + undangan akun ---

test('admin membuat pelanggan tanpa akun', function () {
    $admin = adminPelangganUji();

    $this->actingAs($admin)
        ->post(route('admin.pelanggan.store'), dataPelangganUji())
        ->assertRedirect(route('admin.pelanggan.index'))
        ->assertSessionHasNoErrors();

    $pelanggan = Pelanggan::firstWhere('no_hp', '081300000001');

    expect($pelanggan->status)->toBe(Pelanggan::STATUS_AKTIF)
        ->and($pelanggan->id_pengguna)->toBeNull()
        ->and($pelanggan->nama)->toBe('Budi Bengkel')
        ->and(Pengguna::where('email', 'budi@example.com')->exists())->toBeFalse()
        ->and(aksiAudit('buat_pelanggan'))->toBe(1);
});

test('membuat pelanggan sekaligus mengundang akun customer', function () {
    Notification::fake();
    $admin = adminPelangganUji();

    $this->actingAs($admin)
        ->post(route('admin.pelanggan.store'), dataPelangganUji(['kirim_undangan' => '1']))
        ->assertRedirect(route('admin.pelanggan.index'))
        ->assertSessionHasNoErrors();

    $akun = Pengguna::firstWhere('email', 'budi@example.com');
    $pelanggan = Pelanggan::firstWhere('no_hp', '081300000001');
    $undangan = UndanganAkun::firstWhere('id_pengguna', $akun->id_pengguna);

    expect($akun->peran)->toBe(Pengguna::PERAN_CUSTOMER)
        ->and($akun->status_akun)->toBe(Pengguna::STATUS_MENUNGGU_AKTIVASI)
        ->and($akun->password)->toBeNull()
        ->and($pelanggan->id_pengguna)->toBe($akun->id_pengguna)
        ->and($undangan->token_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and(abs(now()->diffInMinutes($undangan->kedaluwarsa_pada) - UndanganAkun::MASA_BERLAKU_JAM * 60))
        ->toBeLessThan(2)
        ->and(aksiAudit('undang_pelanggan'))->toBe(1)
        ->and(aksiAudit('undang_staf'))->toBe(0);

    Notification::assertSentTo($akun, UndanganAktivasi::class, function (UndanganAktivasi $notifikasi) {
        // Token asli hanya hidup di email; DB menyimpan hash-nya saja.
        expect(hash('sha256', $notifikasi->token))
            ->toBe(UndanganAkun::firstWhere('id_pengguna', $notifikasi->pengguna->id_pengguna)->token_hash);

        Auth::logout();                                     // halaman aktivasi hanya untuk tamu

        $this->get(route('aktivasi.create', ['token' => $notifikasi->token]))
            ->assertOk()
            ->assertSee('Aktifkan akun');

        return true;
    });
});

test('undangan akun wajib beremail dan email yang sudah terdaftar ditolak', function () {
    $admin = adminPelangganUji();
    Pengguna::factory()->create(['email' => 'dipakai@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.pelanggan.store'), dataPelangganUji(['email' => null, 'kirim_undangan' => '1']))
        ->assertSessionHasErrors(['email' => 'Email wajib diisi untuk mengirim undangan akun.']);

    $this->actingAs($admin)
        ->post(route('admin.pelanggan.store'), dataPelangganUji(['email' => 'dipakai@example.com', 'kirim_undangan' => '1']))
        ->assertSessionHasErrors(['email' => 'Email sudah terdaftar.']);

    expect(Pelanggan::count())->toBe(0);
});

test('menolak no hp ganda dan no hp milik akun pengguna lain', function () {
    $admin = adminPelangganUji();
    Pelanggan::create(dataPelangganUji());

    $this->actingAs($admin)
        ->post(route('admin.pelanggan.store'), dataPelangganUji(['no_hp' => '081300000001']))
        ->assertSessionHasErrors(['no_hp' => 'No. HP sudah terdaftar.']);

    Pengguna::factory()->create(['peran' => Pengguna::PERAN_TEKNISI, 'no_hp' => '081300000002']);

    $this->actingAs($admin)
        ->post(route('admin.pelanggan.store'), dataPelangganUji(['no_hp' => '081300000002']))
        ->assertSessionHasErrors(['no_hp' => 'No. HP sudah dipakai akun pengguna lain.']);
});

// --- Fitur 12: daftar dan pencarian ---

test('daftar pelanggan bisa dicari lewat nama, no hp, dan email', function () {
    $admin = adminPelangganUji();
    Pelanggan::create(dataPelangganUji(['nama' => 'Andi Cat', 'no_hp' => '081300000011', 'email' => 'andi@example.com']));
    Pelanggan::create(dataPelangganUji(['nama' => 'Cici Motor', 'no_hp' => '081300000012', 'email' => 'cici@example.com']));

    $this->actingAs($admin)->get(route('admin.pelanggan.index'))
        ->assertOk()->assertSee('Andi Cat')->assertSee('Cici Motor');

    $this->actingAs($admin)->get(route('admin.pelanggan.index', ['cari' => 'Cici']))
        ->assertOk()->assertSee('Cici Motor')->assertDontSee('Andi Cat');

    $this->actingAs($admin)->get(route('admin.pelanggan.index', ['cari' => '081300000011']))
        ->assertOk()->assertSee('Andi Cat')->assertDontSee('Cici Motor');

    $this->actingAs($admin)->get(route('admin.pelanggan.index', ['cari' => 'cici@example.com']))
        ->assertOk()->assertSee('Cici Motor')->assertDontSee('Andi Cat');
});

// --- Fitur 13: ubah data ---

test('mengubah data pelanggan dan mencatat audit', function () {
    $admin = adminPelangganUji();
    $pelanggan = Pelanggan::create(dataPelangganUji());

    $this->actingAs($admin)
        ->put(route('admin.pelanggan.update', $pelanggan), dataPelangganUji([
            'nama' => 'Budi Baru',
            'alamat' => 'Jl. Baru 2',
        ]))
        ->assertRedirect(route('admin.pelanggan.index'))
        ->assertSessionHasNoErrors();

    expect($pelanggan->refresh()->nama)->toBe('Budi Baru')
        ->and($pelanggan->alamat)->toBe('Jl. Baru 2')
        ->and(aksiAudit('ubah_pelanggan'))->toBe(1)
        ->and(aksiAudit('buat_pelanggan'))->toBe(0);
});

test('no hp pelanggan yang sudah punya akun sendiri tetap boleh dipakai', function () {
    $admin = adminPelangganUji();
    $akun = Pengguna::factory()->create(['peran' => Pengguna::PERAN_CUSTOMER, 'no_hp' => '081300000021']);
    $pelanggan = Pelanggan::create(dataPelangganUji(['no_hp' => '081300000021', 'id_pengguna' => $akun->id_pengguna]));

    $this->actingAs($admin)
        ->put(route('admin.pelanggan.update', $pelanggan), dataPelangganUji(['no_hp' => '081300000021', 'nama' => 'Budi Sama']))
        ->assertSessionHasNoErrors();

    expect($pelanggan->refresh()->nama)->toBe('Budi Sama');
});

// --- Fitur 14: nonaktifkan, bukan hapus (BR-17) ---

test('menonaktifkan pelanggan tanpa akun dan mengabaikan pengulangan', function () {
    $admin = adminPelangganUji();
    $pelanggan = Pelanggan::create(dataPelangganUji());

    $this->actingAs($admin)->post(route('admin.pelanggan.nonaktif', $pelanggan))
        ->assertRedirect(route('admin.pelanggan.index'))
        ->assertSessionHas('status', 'Pelanggan Budi Bengkel dinonaktifkan.');

    $this->actingAs($admin)->post(route('admin.pelanggan.nonaktif', $pelanggan))->assertSessionHasNoErrors();

    expect($pelanggan->refresh()->status)->toBe(Pelanggan::STATUS_NONAKTIF)
        ->and(Pelanggan::count())->toBe(1)
        ->and(aksiAudit('nonaktifkan_pelanggan'))->toBe(1);
});

test('menonaktifkan pelanggan ikut mematikan akun yang tertaut', function () {
    $admin = adminPelangganUji();
    $akun = Pengguna::factory()->create([
        'peran' => Pengguna::PERAN_CUSTOMER,
        'no_hp' => '081300000031',
        'status_akun' => Pengguna::STATUS_AKTIF,
    ]);
    $pelanggan = Pelanggan::create(dataPelangganUji(['no_hp' => '081300000031', 'id_pengguna' => $akun->id_pengguna]));

    $this->actingAs($admin)->post(route('admin.pelanggan.nonaktif', $pelanggan))->assertSessionHasNoErrors();

    expect($pelanggan->refresh()->status)->toBe(Pelanggan::STATUS_NONAKTIF)
        ->and($akun->refresh()->status_akun)->toBe(Pengguna::STATUS_NONAKTIF)
        ->and(aksiAudit('nonaktifkan_pelanggan'))->toBe(1);
});

test('pelanggan nonaktif tetap tampil dan tidak ada jalur hapus permanen', function () {
    $admin = adminPelangganUji();
    $pelanggan = Pelanggan::create(dataPelangganUji(['nama' => 'Budi Nonaktif']));
    $pelanggan->status = Pelanggan::STATUS_NONAKTIF;
    $pelanggan->save();

    $this->actingAs($admin)
        ->get(route('admin.pelanggan.index'))
        ->assertOk()
        ->assertSee('Budi Nonaktif')
        ->assertSee('Nonaktif');

    $this->actingAs($admin)
        ->delete(route('admin.pelanggan.update', $pelanggan))
        ->assertStatus(405);
});

// --- Hak akses ---

test('halaman pelanggan hanya untuk admin dan owner', function () {
    $this->get(route('admin.pelanggan.index'))->assertRedirect(route('login'));

    $this->actingAs(Pengguna::factory()->create(['peran' => Pengguna::PERAN_CUSTOMER]))
        ->get(route('admin.pelanggan.index'))->assertForbidden();

    $this->actingAs(Pengguna::factory()->create(['peran' => Pengguna::PERAN_TEKNISI]))
        ->post(route('admin.pelanggan.store'), dataPelangganUji())->assertForbidden();

    $this->actingAs(adminPelangganUji())->get(route('admin.pelanggan.index'))->assertOk()->assertSee('Pelanggan baru');

    $this->actingAs(ownerPelangganUji())->get(route('admin.pelanggan.index'))->assertOk()->assertSee('Pelanggan baru');

    expect(Pelanggan::count())->toBe(0);
});

test('akun nonaktif tidak bisa mengelola pelanggan', function () {
    $this->actingAs(Pengguna::factory()->create([
        'peran' => Pengguna::PERAN_ADMIN,
        'status_akun' => Pengguna::STATUS_NONAKTIF,
    ]))->get(route('admin.pelanggan.index'))->assertForbidden();
});
