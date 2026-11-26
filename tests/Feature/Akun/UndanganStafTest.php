<?php

use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Models\UndanganAkun;
use App\Notifications\UndanganAktivasi;
use Illuminate\Support\Facades\Notification;

function dataUndanganStaf(array $ubah = []): array
{
    return $ubah + [
        'nama' => 'Teknisi Baru',
        'email' => 'teknisi@example.com',
        'no_hp' => '081200000001',
        'peran' => Pengguna::PERAN_TEKNISI,
    ];
}

function owner(): Pengguna
{
    return Pengguna::factory()->create(['peran' => Pengguna::PERAN_OWNER]);
}

test('halaman kelola staf hanya dapat dibuka owner', function () {
    $this->get('/admin/staf')->assertRedirect('/login');

    $this->actingAs(Pengguna::factory()->create(['peran' => Pengguna::PERAN_ADMIN]))
        ->get('/admin/staf')->assertForbidden();

    $this->actingAs(Pengguna::factory()->create(['peran' => Pengguna::PERAN_TEKNISI]))
        ->get('/admin/staf')->assertForbidden();

    $this->actingAs(owner())->get('/admin/staf')->assertOk();
});

test('hanya owner yang dapat mengundang staf', function () {
    $this->post('/admin/staf', dataUndanganStaf())->assertRedirect('/login');

    $this->actingAs(Pengguna::factory()->create(['peran' => Pengguna::PERAN_TEKNISI]))
        ->post('/admin/staf', dataUndanganStaf())->assertForbidden();

    expect(Pengguna::count())->toBe(1);
});

test('owner mengundang staf: pengguna menunggu_aktivasi tanpa password dan token tersimpan sebagai hash', function () {
    Notification::fake();
    $this->freezeTime();

    $owner = owner();

    $this->actingAs($owner)->post('/admin/staf', dataUndanganStaf())
        ->assertRedirect(route('admin.staf.index', absolute: false));

    $staf = Pengguna::firstWhere('email', 'teknisi@example.com');

    expect($staf)->not->toBeNull()
        ->and($staf->nama)->toBe('Teknisi Baru')
        ->and($staf->peran)->toBe(Pengguna::PERAN_TEKNISI)
        ->and($staf->status_akun)->toBe(Pengguna::STATUS_MENUNGGU_AKTIVASI)
        ->and($staf->password)->toBeNull();

    $undangan = UndanganAkun::firstWhere('id_pengguna', $staf->id_pengguna);

    expect($undangan)->not->toBeNull()
        ->and($undangan->dipakai_pada)->toBeNull()
        ->and($undangan->dibuat_oleh)->toBe($owner->id_pengguna)
        ->and($undangan->kedaluwarsa_pada->timestamp)->toBe(now()->addHours(72)->timestamp)
        ->and($undangan->token_hash)->toHaveLength(64);

    Notification::assertSentTo($staf, UndanganAktivasi::class, function (UndanganAktivasi $notifikasi, array $saluran) use ($staf, $undangan) {
        expect($saluran)->toContain('mail')
            ->and($notifikasi->pengguna->id_pengguna)->toBe($staf->id_pengguna)
            ->and($notifikasi->token)->toHaveLength(64)
            ->and($notifikasi->token)->not->toBe($undangan->token_hash)
            ->and(hash('sha256', $notifikasi->token))->toBe($undangan->token_hash)
            ->and($notifikasi->tautan)->toBe(url('/aktivasi/'.$notifikasi->token));

        return true;
    });
});

test('undangan menolak email yang sudah terdaftar', function () {
    Notification::fake();
    Pengguna::factory()->create(['email' => 'teknisi@example.com']);

    $this->actingAs(owner())->post('/admin/staf', dataUndanganStaf())
        ->assertSessionHasErrors(['email' => 'Email sudah terdaftar.']);

    expect(Pengguna::count())->toBe(2)
        ->and(UndanganAkun::count())->toBe(0);
    Notification::assertNothingSent();
});

test('undangan menolak no hp yang sudah dipakai pengguna atau pelanggan tanpa akun', function () {
    Pengguna::factory()->create(['no_hp' => '081200000002']);
    Pelanggan::create(['nama' => 'Pelanggan Toko', 'no_hp' => '081200000003']);

    $this->actingAs(owner());

    $this->post('/admin/staf', dataUndanganStaf(['no_hp' => '081200000002']))
        ->assertSessionHasErrors(['no_hp' => 'No. HP sudah terdaftar.']);

    $this->post('/admin/staf', dataUndanganStaf(['no_hp' => '081200000003']))
        ->assertSessionHasErrors(['no_hp' => 'No. HP sudah terdaftar.']);

    expect(UndanganAkun::count())->toBe(0);
});

test('undangan hanya menerima peran admin atau teknisi', function () {
    $this->actingAs(owner());

    $this->post('/admin/staf', dataUndanganStaf(['peran' => Pengguna::PERAN_OWNER]))
        ->assertSessionHasErrors(['peran' => 'Peran hanya boleh admin atau teknisi.']);

    $this->post('/admin/staf', dataUndanganStaf(['peran' => Pengguna::PERAN_CUSTOMER]))
        ->assertSessionHasErrors('peran');

    $this->post('/admin/staf', dataUndanganStaf(['peran' => Pengguna::PERAN_ADMIN]))
        ->assertSessionHasNoErrors();

    expect(Pengguna::where('peran', Pengguna::PERAN_ADMIN)->count())->toBe(1);
});

test('peran, status akun, dan password dari input undangan diabaikan', function () {
    Notification::fake();

    $this->actingAs(owner())->post('/admin/staf', dataUndanganStaf([
        'peran' => Pengguna::PERAN_ADMIN,
        'status_akun' => Pengguna::STATUS_AKTIF,
        'password' => 'rahasia123',
    ]));

    $staf = Pengguna::firstWhere('email', 'teknisi@example.com');

    expect($staf->peran)->toBe(Pengguna::PERAN_ADMIN)
        ->and($staf->status_akun)->toBe(Pengguna::STATUS_MENUNGGU_AKTIVASI)
        ->and($staf->password)->toBeNull();
});

test('undangan staf tercatat di audit log', function () {
    Notification::fake();
    $owner = owner();

    $this->actingAs($owner)->post('/admin/staf', dataUndanganStaf());

    $staf = Pengguna::firstWhere('email', 'teknisi@example.com');

    $this->assertDatabaseHas('audit_log', [
        'id_pengguna' => $owner->id_pengguna,
        'aksi' => 'undang_staf',
        'objek_tipe' => 'Pengguna',
        'objek_id' => $staf->id_pengguna,
    ]);
});

test('akun hasil undangan belum dapat login sebelum aktivasi', function () {
    Notification::fake();
    $this->actingAs(owner())->post('/admin/staf', dataUndanganStaf());

    auth()->logout();

    $this->post('/login', ['email' => 'teknisi@example.com', 'password' => 'apa-saja'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
