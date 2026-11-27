<?php

use App\Models\Estimasi;
use App\Models\ItemEstimasi;
use App\Models\Pengguna;
use App\Models\Servis;
use Illuminate\Support\Facades\DB;

/*
| Tahap 4 — fitur 24, 25: draf estimasi, snapshot harga, total server, kirim.
*/

it('menyimpan draf estimasi dengan harga snapshot dari master, bukan dari form', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());
    $tarif = tahap4Tarif('Ganti LCD', 350000);
    $sparepart = tahap4Sparepart('LCD 14 inci', 750000);

    $this->actingAs($admin)->post(route('admin.servis.estimasi.simpan', $servis), [
        'items' => [
            ['sumber' => 'jasa:'.$tarif->id_tarif, 'jumlah' => 1, 'harga_satuan_salinan' => 1],
            ['sumber' => 'sparepart:'.$sparepart->id_sparepart, 'jumlah' => 2, 'harga_satuan_salinan' => 1],
        ],
    ])->assertRedirect(route('admin.servis.show', $servis));

    $estimasi = $servis->estimasi()->firstOrFail();
    $estimasi->load('item');

    expect($estimasi->versi)->toBe(1)
        ->and($estimasi->status)->toBe(Estimasi::DRAF)
        ->and($estimasi->item)->toHaveCount(2)
        ->and((float) $estimasi->item[0]->harga_satuan_salinan)->toBe(350000.0)
        ->and((float) $estimasi->item[1]->harga_satuan_salinan)->toBe(750000.0)
        ->and($estimasi->item[1]->jumlah)->toBe(2)
        ->and($estimasi->total())->toBe(1850000.0);
});

it('menyimpan ulang draf mengganti seluruh item pada versi yang sama', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());
    $tarif = tahap4Tarif('Ganti LCD', 350000);
    $sparepart = tahap4Sparepart('LCD 14 inci', 750000);

    $this->actingAs($admin)->post(route('admin.servis.estimasi.simpan', $servis), [
        'items' => [['sumber' => 'jasa:'.$tarif->id_tarif, 'jumlah' => 1]],
    ]);

    $this->actingAs($admin)->post(route('admin.servis.estimasi.simpan', $servis), [
        'items' => [['sumber' => 'sparepart:'.$sparepart->id_sparepart, 'jumlah' => 1]],
    ])->assertRedirect(route('admin.servis.show', $servis));

    $estimasi = $servis->estimasi()->get();

    expect($estimasi)->toHaveCount(1)
        ->and($estimasi->first()->versi)->toBe(1)
        ->and(ItemEstimasi::count())->toBe(1)
        ->and(ItemEstimasi::first()->jenis)->toBe(ItemEstimasi::SPAREPART);
});

it('harga master yang berubah tidak mengubah item estimasi lama (snapshot)', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());
    $tarif = tahap4Tarif('Ganti LCD', 350000);

    $this->actingAs($admin)->post(route('admin.servis.estimasi.simpan', $servis), [
        'items' => [['sumber' => 'jasa:'.$tarif->id_tarif, 'jumlah' => 1]],
    ]);

    $tarif->update(['harga' => 999000]);

    expect((float) $servis->estimasi()->firstOrFail()->item()->first()->harga_satuan_salinan)->toBe(350000.0);
});

it('menolak item yang merujuk tarif atau spare part nonaktif', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());
    $sparepart = tahap4Sparepart();
    DB::table('sparepart')->where('id_sparepart', $sparepart->id_sparepart)->update(['status' => 'nonaktif']);

    $this->actingAs($admin)->post(route('admin.servis.estimasi.simpan', $servis), [
        'items' => [['sumber' => 'sparepart:'.$sparepart->id_sparepart, 'jumlah' => 1]],
    ])->assertSessionHasErrors('items');

    expect($servis->estimasi()->count())->toBe(0);
});

it('menolak draf tanpa item dan sumber yang tidak dikenal', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());

    $this->actingAs($admin)->post(route('admin.servis.estimasi.simpan', $servis), [
        'items' => [],
    ])->assertSessionHasErrors('items');

    $this->actingAs($admin)->post(route('admin.servis.estimasi.simpan', $servis), [
        'items' => [['sumber' => 'lain:1', 'jumlah' => 1]],
    ])->assertSessionHasErrors('items.0.sumber');

    expect($servis->estimasi()->count())->toBe(0);
});

it('kirim estimasi butuh diagnosis dan tidak boleh dari status yang salah', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());
    $tarif = tahap4Tarif('Ganti LCD', 350000);

    $this->actingAs($admin)->post(route('admin.servis.estimasi.simpan', $servis), [
        'items' => [['sumber' => 'jasa:'.$tarif->id_tarif, 'jumlah' => 1]],
    ]);

    $estimasi = $servis->estimasi()->firstOrFail();

    // Tanpa diagnosis: kirim ditolak, status tetap draf.
    $this->actingAs($admin)->post(route('admin.servis.estimasi.kirim', [$servis, $estimasi]))
        ->assertSessionHasErrors('estimasi');

    expect($estimasi->refresh()->status)->toBe(Estimasi::DRAF)
        ->and($servis->refresh()->status)->toBe(Servis::MENUNGGU_DIAGNOSIS);
});

it('kirim estimasi memindahkan servis ke menunggu persetujuan dan mencatat audit', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());

    $estimasi = tahap4EstimasiTerkirim($servis, $admin);

    expect($estimasi->refresh()->status)->toBe(Estimasi::DIKIRIM)
        ->and($estimasi->divalidasi_oleh)->toBe($admin->id_pengguna)
        ->and($estimasi->dikirim_pada)->not->toBeNull()
        ->and($servis->refresh()->status)->toBe(Servis::MENUNGGU_PERSETUJUAN)
        ->and(DB::table('audit_log')->where('aksi', 'kirim_estimasi')->where('objek_tipe', 'Servis')->where('objek_id', $servis->id_servis)->count())->toBe(1);
});

it('estimasi yang sudah dikirim tidak bisa dikirim ulang', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());
    $estimasi = tahap4EstimasiTerkirim($servis, $admin);

    $this->actingAs($admin)->post(route('admin.servis.estimasi.kirim', [$servis, $estimasi]))
        ->assertSessionHasErrors('estimasi');

    expect($servis->refresh()->status)->toBe(Servis::MENUNGGU_PERSETUJUAN);
});
