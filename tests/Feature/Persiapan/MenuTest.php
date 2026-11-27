<?php

use App\Support\Menu;
use Illuminate\Support\Facades\Route;

$rute = fn (array $menu) => collect($menu)->pluck('route')->all();

it('hanya menampilkan item yang route-nya sudah terdaftar', function () use ($rute) {
    expect($rute(Menu::untuk(null)))->toBe(['publik.beranda', 'publik.lacak.index', 'login', 'register'])
        ->and($rute(Menu::untuk('teknisi')))->toBe(['teknisi.pekerjaan.index']);
});

it('item muncul begitu route-nya terdaftar', function () use ($rute) {
    Route::get('/lacak', fn () => 'ok')->name('publik.lacak.index');

    expect($rute(Menu::untuk(null)))->toContain('publik.lacak.index');
});

it('owner mendapat menu khusus owner, admin tidak', function () use ($rute) {
    $khususOwner = ['admin.staf.index', 'admin.pengaturan.edit', 'admin.tarif-jasa.index'];

    foreach ($khususOwner as $i => $nama) {
        Route::get('/r'.$i, fn () => 'ok')->name($nama);
    }
    Route::get('/admin', fn () => 'ok')->name('admin.dashboard');
    Route::get('/admin/pelanggan', fn () => 'ok')->name('admin.pelanggan.index');
    Route::get('/admin/sparepart', fn () => 'ok')->name('admin.sparepart.index');

    expect($rute(Menu::untuk('owner')))
        ->toBe(['admin.dashboard', 'admin.servis.index', 'admin.pekerjaan.index', 'admin.pelanggan.index', 'admin.sparepart.index', ...$khususOwner])
        ->and($rute(Menu::untuk('admin')))->not->toContain($khususOwner[0]);
});
