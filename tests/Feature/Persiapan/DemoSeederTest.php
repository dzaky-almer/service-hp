<?php

use App\Models\Pengguna;
use App\Models\Servis;
use Database\Seeders\DemoSeeder;

it('menanam data demo: akun per peran, tiga servis, satu lunas, satu lewat SLA', function () {
    $this->seed();
    $this->seed(DemoSeeder::class);

    expect(Pengguna::where('email', 'admin@demo.test')->first()->peran)->toBe(Pengguna::PERAN_ADMIN)
        ->and(Pengguna::where('email', 'teknisi@demo.test')->first()->peran)->toBe(Pengguna::PERAN_TEKNISI)
        ->and(Pengguna::where('email', 'customer@demo.test')->first()->peran)->toBe(Pengguna::PERAN_CUSTOMER);

    expect(Servis::count())->toBe(3)
        ->and(Servis::where('status', Servis::SIAP_DIAMBIL)->count())->toBe(1)
        ->and(Servis::where('status', Servis::DIKERJAKAN)->count())->toBe(1)
        ->and(Servis::where('status', Servis::DITERIMA)->count())->toBe(1)
        ->and(Servis::query()->terlambat()->count())->toBe(1);

    $lunas = Servis::where('status', Servis::SIAP_DIAMBIL)->first();
    expect($lunas->lunas())->toBeTrue();

    // Password akun demo bisa dipakai login.
    $this->post('/login', ['email' => 'admin@demo.test', 'password' => 'rahasia123'])
        ->assertRedirect('/dashboard');
    $this->assertAuthenticated();
});

it('tidak menggandakan data bila dijalankan dua kali', function () {
    $this->seed();
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Servis::count())->toBe(3)
        ->and(Pengguna::where('email', 'admin@demo.test')->count())->toBe(1);
});
