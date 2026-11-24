<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

it('menanam data awal: owner aktif, pengaturan bisnis, tarif jasa, spare part berstok', function () {
    $this->seed();

    $owner = DB::table('pengguna')->where('email', config('servis.owner_email'))->first();

    expect($owner)->not->toBeNull()
        ->and($owner->peran)->toBe('owner')
        ->and($owner->status_akun)->toBe('aktif')
        ->and(Hash::check(config('servis.owner_password'), $owner->password))->toBeTrue();

    expect(DB::table('pengaturan_bisnis')->pluck('nilai', 'kunci')->all())
        ->toMatchArray([
            'sla_hari' => '7',
            'biaya_pemeriksaan' => '0',
        ]);

    expect(DB::table('tarif_jasa')->where('status', 'aktif')->count())->toBeGreaterThan(0);
    expect(DB::table('sparepart')->count())->toBeGreaterThan(0);

    $stokMasuk = DB::table('mutasi_stok')
        ->where('jenis', 'masuk')
        ->select('id_sparepart', DB::raw('SUM(jumlah) as total'))
        ->groupBy('id_sparepart')
        ->pluck('total', 'id_sparepart');

    expect($stokMasuk)->toHaveCount(DB::table('sparepart')->count())
        ->and((int) $stokMasuk->min())->toBeGreaterThan(0);
});

it('idempoten: menjalankan seeder dua kali tidak menggandakan data', function () {
    $this->seed();

    $jumlah = fn () => [
        DB::table('pengguna')->count(),
        DB::table('tarif_jasa')->count(),
        DB::table('sparepart')->count(),
        DB::table('mutasi_stok')->count(),
    ];

    $sekali = $jumlah();

    $this->seed();

    expect($jumlah())->toBe($sekali)
        ->and($sekali[1])->toBeGreaterThan(0)
        ->and($sekali[2])->toBeGreaterThan(0)
        ->and($sekali[3])->toBe($sekali[2]);
});
