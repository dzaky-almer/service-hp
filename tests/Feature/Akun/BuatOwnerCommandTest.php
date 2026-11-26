<?php

use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('owner:buat membuat akun owner aktif yang langsung dapat login', function () {
    $this->artisan('owner:buat', [
        'email' => 'owner.baru@example.com',
        '--nama' => 'Owner Baru',
        '--password' => 'RahasiaOwner123',
    ])
        ->expectsOutput('Akun Owner owner.baru@example.com dibuat dan berstatus aktif.')
        ->assertExitCode(0);

    $owner = Pengguna::firstWhere('email', 'owner.baru@example.com');

    expect($owner)->not->toBeNull()
        ->and($owner->peran)->toBe(Pengguna::PERAN_OWNER)
        ->and($owner->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and($owner->nama)->toBe('Owner Baru')
        ->and($owner->password)->not->toBe('RahasiaOwner123')
        ->and(Hash::check('RahasiaOwner123', $owner->password))->toBeTrue();

    $this->post('/login', ['email' => 'owner.baru@example.com', 'password' => 'RahasiaOwner123'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($owner);
});

test('owner:buat menanyakan nama, email, dan password bila tidak lewat opsi', function () {
    $this->artisan('owner:buat')
        ->expectsQuestion('Nama', 'Owner Prompt')
        ->expectsQuestion('Email', 'owner.prompt@example.com')
        ->expectsQuestion('Password', 'RahasiaOwner123')
        ->expectsQuestion('Ulangi password', 'RahasiaOwner123')
        ->assertExitCode(0);

    $owner = Pengguna::firstWhere('email', 'owner.prompt@example.com');

    expect($owner)->not->toBeNull()
        ->and($owner->peran)->toBe(Pengguna::PERAN_OWNER)
        ->and($owner->status_akun)->toBe(Pengguna::STATUS_AKTIF)
        ->and(Hash::check('RahasiaOwner123', $owner->password))->toBeTrue();
});

test('owner:buat menolak email yang sudah terdaftar', function () {
    Pengguna::factory()->create(['email' => 'owner.baru@example.com']);

    $this->artisan('owner:buat', [
        'email' => 'owner.baru@example.com',
        '--nama' => 'Owner Baru',
        '--password' => 'RahasiaOwner123',
    ])
        ->expectsOutput('Email sudah terdaftar.')
        ->assertExitCode(1);

    expect(Pengguna::count())->toBe(1)
        ->and(DB::table('audit_log')->count())->toBe(0);
});

test('owner:buat menolak email tidak valid dan password lemah', function () {
    $this->artisan('owner:buat', [
        'email' => 'bukan-email',
        '--nama' => 'Owner Baru',
        '--password' => 'pendek',
    ])
        ->expectsOutput('Format email tidak valid.')
        ->expectsOutput('Password minimal 8 karakter.')
        ->assertExitCode(1);

    expect(Pengguna::count())->toBe(0)
        ->and(DB::table('audit_log')->count())->toBe(0);
});

test('owner:buat menolak konfirmasi password yang tidak cocok', function () {
    $this->artisan('owner:buat')
        ->expectsQuestion('Nama', 'Owner Prompt')
        ->expectsQuestion('Email', 'owner.prompt@example.com')
        ->expectsQuestion('Password', 'RahasiaOwner123')
        ->expectsQuestion('Ulangi password', 'RahasiaLain123')
        ->expectsOutput('Konfirmasi password tidak cocok.')
        ->assertExitCode(1);

    expect(Pengguna::count())->toBe(0)
        ->and(DB::table('audit_log')->count())->toBe(0);
});

test('owner:buat mencatat aksi buat_owner di audit log tanpa pelaku', function () {
    $this->artisan('owner:buat', [
        'email' => 'owner.baru@example.com',
        '--nama' => 'Owner Baru',
        '--password' => 'RahasiaOwner123',
    ])->assertExitCode(0);

    $owner = Pengguna::firstWhere('email', 'owner.baru@example.com');
    $audit = DB::table('audit_log')->where('aksi', 'buat_owner')->first();

    expect($audit)->not->toBeNull()
        ->and($audit->id_pengguna)->toBeNull()
        ->and($audit->objek_tipe)->toBe('Pengguna')
        ->and($audit->objek_id)->toBe($owner->id_pengguna)
        ->and(json_decode($audit->data_sesudah, true))->toEqual([
            'peran' => Pengguna::PERAN_OWNER,
            'email' => 'owner.baru@example.com',
            'status_akun' => Pengguna::STATUS_AKTIF,
        ]);
});

test('owner:buat tidak membuat baris undangan', function () {
    $this->artisan('owner:buat', [
        'email' => 'owner.baru@example.com',
        '--nama' => 'Owner Baru',
        '--password' => 'RahasiaOwner123',
    ])->assertExitCode(0);

    $this->assertDatabaseCount('undangan_akun', 0);
});
