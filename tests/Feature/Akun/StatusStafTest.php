<?php

use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

function ownerUji(): Pengguna
{
    return Pengguna::factory()->create(['peran' => Pengguna::PERAN_OWNER]);
}

/** Baris audit_log terakhir sebagai array, atau null bila belum ada. */
function auditLogTerakhir(): ?array
{
    $baris = DB::table('audit_log')->orderByDesc('id_audit')->first();

    return $baris === null ? null : (array) $baris;
}

function stafUji(string $peran = Pengguna::PERAN_TEKNISI, string $status = Pengguna::STATUS_AKTIF): Pengguna
{
    return Pengguna::factory()->create(['peran' => $peran, 'status_akun' => $status]);
}

function urlStatusStaf(Pengguna $staf): string
{
    return '/admin/staf/'.$staf->id_pengguna.'/status';
}

test('ubah status staf hanya dapat dilakukan owner', function () {
    $staf = stafUji();

    $this->post(urlStatusStaf($staf), ['status_akun' => Pengguna::STATUS_NONAKTIF])
        ->assertRedirect('/login');

    $this->actingAs(stafUji(Pengguna::PERAN_ADMIN))
        ->post(urlStatusStaf($staf), ['status_akun' => Pengguna::STATUS_NONAKTIF])
        ->assertForbidden();

    $this->actingAs(stafUji(Pengguna::PERAN_TEKNISI))
        ->post(urlStatusStaf($staf), ['status_akun' => Pengguna::STATUS_NONAKTIF])
        ->assertForbidden();

    expect($staf->fresh()->status_akun)->toBe(Pengguna::STATUS_AKTIF);
});

test('owner menonaktifkan staf aktif dan aksinya tercatat di audit log', function () {
    $owner = ownerUji();
    $staf = stafUji();

    $this->actingAs($owner)->post(urlStatusStaf($staf), ['status_akun' => Pengguna::STATUS_NONAKTIF])
        ->assertRedirect(route('admin.staf.index', absolute: false))
        ->assertSessionHas('status', $staf->nama.' kini berstatus Nonaktif.');

    expect($staf->fresh()->status_akun)->toBe(Pengguna::STATUS_NONAKTIF);

    $this->assertDatabaseHas('audit_log', [
        'id_pengguna' => $owner->id_pengguna,
        'aksi' => 'nonaktifkan_staf',
        'objek_tipe' => 'Pengguna',
        'objek_id' => $staf->id_pengguna,
    ]);

    expect(json_decode(auditLogTerakhir()['data_sebelum'], true))->toBe(['status_akun' => Pengguna::STATUS_AKTIF])
        ->and(json_decode(auditLogTerakhir()['data_sesudah'], true))->toBe(['status_akun' => Pengguna::STATUS_NONAKTIF]);
});

test('owner mengaktifkan kembali staf nonaktif dan aksinya tercatat di audit log', function () {
    $owner = ownerUji();
    $staf = stafUji(status: Pengguna::STATUS_NONAKTIF);

    $this->actingAs($owner)->post(urlStatusStaf($staf), ['status_akun' => Pengguna::STATUS_AKTIF])
        ->assertRedirect(route('admin.staf.index', absolute: false))
        ->assertSessionHas('status', $staf->nama.' kini berstatus Aktif.');

    expect($staf->fresh()->status_akun)->toBe(Pengguna::STATUS_AKTIF);

    $this->assertDatabaseHas('audit_log', [
        'id_pengguna' => $owner->id_pengguna,
        'aksi' => 'aktifkan_staf',
        'objek_id' => $staf->id_pengguna,
    ]);
});

test('staf yang dinonaktifkan tidak dapat login sampai diaktifkan kembali', function () {
    $owner = ownerUji();
    $staf = stafUji();
    $staf->forceFill(['password' => 'PasswordStaf123'])->save();

    $this->actingAs($owner)->post(urlStatusStaf($staf), ['status_akun' => Pengguna::STATUS_NONAKTIF]);
    auth()->logout();

    $this->post('/login', ['email' => $staf->email, 'password' => 'PasswordStaf123'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->actingAs($owner)->post(urlStatusStaf($staf), ['status_akun' => Pengguna::STATUS_AKTIF]);
    auth()->logout();

    $this->post('/login', ['email' => $staf->email, 'password' => 'PasswordStaf123'])->assertRedirect();
    $this->assertAuthenticatedAs($staf->fresh());
});

test('owner tidak dapat mengubah status akunnya sendiri', function () {
    $owner = ownerUji();

    $this->actingAs($owner)->post(urlStatusStaf($owner), ['status_akun' => Pengguna::STATUS_NONAKTIF])
        ->assertForbidden();

    expect($owner->fresh()->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and(auditLogTerakhir())->toBeNull();
});

test('akun owner lain tidak dapat dinonaktifkan', function () {
    $owner = ownerUji();
    $ownerLain = ownerUji();

    $this->actingAs($owner)->post(urlStatusStaf($ownerLain), ['status_akun' => Pengguna::STATUS_NONAKTIF])
        ->assertForbidden();

    expect($ownerLain->fresh()->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and(auditLogTerakhir())->toBeNull();
});

test('akun menunggu aktivasi tidak dapat diaktifkan lewat halaman staf', function () {
    $owner = ownerUji();
    $staf = stafUji(status: Pengguna::STATUS_MENUNGGU_AKTIVASI);

    $this->actingAs($owner)->post(urlStatusStaf($staf), ['status_akun' => Pengguna::STATUS_AKTIF])
        ->assertForbidden();

    expect($staf->fresh()->status_akun)->toBe(Pengguna::STATUS_MENUNGGU_AKTIVASI)
        ->and(auditLogTerakhir())->toBeNull();
});

test('status akun di luar aktif dan nonaktif ditolak', function () {
    $owner = ownerUji();
    $staf = stafUji();

    $this->actingAs($owner)->post(urlStatusStaf($staf), ['status_akun' => Pengguna::PERAN_OWNER])
        ->assertSessionHasErrors(['status_akun' => 'Status akun hanya boleh aktif atau nonaktif.']);

    $this->actingAs($owner)->post(urlStatusStaf($staf), ['status_akun' => Pengguna::STATUS_MENUNGGU_AKTIVASI])
        ->assertSessionHasErrors('status_akun');

    $this->actingAs($owner)->post(urlStatusStaf($staf), [])->assertSessionHasErrors('status_akun');

    expect($staf->fresh()->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and($staf->fresh()->peran)->toBe(Pengguna::PERAN_TEKNISI)
        ->and(auditLogTerakhir())->toBeNull();
});

test('peran tidak berubah walau dikirim di body ubah status', function () {
    $owner = ownerUji();
    $staf = stafUji();

    $this->actingAs($owner)->post(urlStatusStaf($staf), [
        'status_akun' => Pengguna::STATUS_NONAKTIF,
        'peran' => Pengguna::PERAN_OWNER,
    ]);

    expect($staf->fresh()->peran)->toBe(Pengguna::PERAN_TEKNISI)
        ->and($staf->fresh()->status_akun)->toBe(Pengguna::STATUS_NONAKTIF);
});
