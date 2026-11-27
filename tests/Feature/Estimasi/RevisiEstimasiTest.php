<?php

use App\Models\Estimasi;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Services\EstimasiService;
use App\Services\ServisService;

/*
| Tahap 4 — fitur 28: revisi estimasi (versi baru) + fitur 26 (kembalikan).
*/

it('revisi membuat versi baru berisi salinan item versi disetujui dan menaikkan versi', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());
    $versiSatu = tahap4EstimasiTerkirim($servis, $admin);

    app(EstimasiService::class)->putuskan($versiSatu, true, $servis->pelanggan->pengguna);
    app(ServisService::class)->pindahStatus($servis->refresh(), Servis::DIKERJAKAN, $admin);

    $this->actingAs($admin)->post(route('admin.servis.estimasi.revisi', $servis))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.servis.show', $servis));

    expect($servis->estimasi()->orderBy('versi')->get()->map(fn ($e) => $e->versi.':'.$e->status)->all())
        ->toBe(['1:disetujui', '2:draf']);

    $versiDua = $servis->estimasiTerakhir();

    expect($versiDua->versi)->toBe(2)
        ->and($versiDua->status)->toBe(Estimasi::DRAF)
        ->and($versiDua->item)->toHaveCount(1)
        ->and($versiDua->item->first()->deskripsi)->toBe('Ganti LCD')
        ->and($servis->estimasiDisetujui()->id_estimasi)->toBe($versiSatu->id_estimasi);
});

it('revisi saat draf masih ada mengembalikan draf yang sama tanpa versi baru', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());
    $tarif = tahap4Tarif();

    $this->actingAs($admin)->post(route('admin.servis.estimasi.simpan', $servis), [
        'items' => [['sumber' => 'jasa:'.$tarif->id_tarif, 'jumlah' => 1]],
    ]);

    $this->actingAs($admin)->post(route('admin.servis.estimasi.revisi', $servis))
        ->assertRedirect(route('admin.servis.show', $servis));

    expect($servis->estimasi()->count())->toBe(1);
});

it('versi yang sudah dikirim tidak bisa diubah lewat simpan draf', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());
    $estimasi = tahap4EstimasiTerkirim($servis, $admin);
    $tarif = tahap4Tarif('Servis tambahan', 100000);

    // Servis kini menunggu persetujuan: draf baru ditolak.
    $this->actingAs($admin)->post(route('admin.servis.estimasi.simpan', $servis), [
        'items' => [['sumber' => 'jasa:'.$tarif->id_tarif, 'jumlah' => 1]],
    ])->assertSessionHasErrors('estimasi');

    expect($estimasi->refresh()->status)->toBe(Estimasi::DIKIRIM)
        ->and($estimasi->item->first()->deskripsi)->toBe('Ganti LCD')
        ->and($servis->estimasi()->count())->toBe(1);
});

it('setelah revisi dikirim, versi lama tetap tersimpan sebagai riwayat', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());
    $versiSatu = tahap4EstimasiTerkirim($servis, $admin, 350000);

    app(EstimasiService::class)->putuskan($versiSatu, true, $servis->pelanggan->pengguna);
    app(ServisService::class)->pindahStatus($servis->refresh(), Servis::DIKERJAKAN, $admin);

    $this->actingAs($admin)->post(route('admin.servis.estimasi.revisi', $servis));
    $versiDua = $servis->estimasiTerakhir();

    $this->actingAs($admin)->post(route('admin.servis.estimasi.kirim', [$servis, $versiDua]))
        ->assertRedirect(route('admin.servis.show', $servis));

    expect($versiDua->refresh()->status)->toBe(Estimasi::DIKIRIM)
        ->and($versiSatu->refresh()->status)->toBe(Estimasi::DISETUJUI)
        ->and($servis->refresh()->status)->toBe(Servis::MENUNGGU_PERSETUJUAN)
        ->and($servis->estimasi()->count())->toBe(2);
});

it('admin menandai siap diambil setelah estimasi ditolak', function () {
    [, $pelanggan] = [null, tahap4Pelanggan()];
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, $pelanggan);
    $estimasi = tahap4EstimasiTerkirim($servis, $admin);

    app(EstimasiService::class)->putuskan($estimasi, false, $servis->pelanggan->pengguna);

    $this->actingAs($admin)->post(route('admin.servis.kembalikan', $servis->refresh()))
        ->assertRedirect(route('admin.servis.show', $servis));

    expect($servis->refresh()->status)->toBe(Servis::SIAP_DIAMBIL);
});

it('kembalikan hanya boleh dari status ditolak', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());

    $this->actingAs($admin)->post(route('admin.servis.kembalikan', $servis))
        ->assertSessionHasErrors('status');

    expect($servis->refresh()->status)->toBe(Servis::MENUNGGU_DIAGNOSIS);
});
