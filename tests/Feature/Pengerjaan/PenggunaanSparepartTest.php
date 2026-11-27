<?php

use App\Models\Sparepart;
use App\Services\StokService;

it('pemakaian spare part mengurangi stok dan menyalin harga beli', function () {
    $uji = tahap5Dikerjakan();
    $sparepart = tahap4Sparepart('LCD 14 inci', 750000); // harga_beli 525.000
    app(StokService::class)->catatMasuk($sparepart, 5, 'stok awal', $uji['admin']);

    $this->actingAs($uji['teknisi'])
        ->post(route('teknisi.pekerjaan.pakai-sparepart', $uji['servis']), [
            'id_sparepart' => $sparepart->id_sparepart,
            'jumlah' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect(app(StokService::class)->stok($sparepart))->toBe(3);
    $this->assertDatabaseHas('penggunaan_sparepart', [
        'id_servis' => $uji['servis']->id_servis,
        'id_sparepart' => $sparepart->id_sparepart,
        'jumlah' => 2,
        'harga_beli_salinan' => 525000,
    ]);
    $this->assertDatabaseHas('mutasi_stok', [
        'id_sparepart' => $sparepart->id_sparepart,
        'id_servis' => $uji['servis']->id_servis,
        'jumlah' => -2,
    ]);
});

it('pemakaian melebihi stok ditolak tanpa mutasi', function () {
    $uji = tahap5Dikerjakan();
    $sparepart = tahap4Sparepart();
    app(StokService::class)->catatMasuk($sparepart, 1, 'stok awal', $uji['admin']);

    $this->actingAs($uji['teknisi'])
        ->post(route('teknisi.pekerjaan.pakai-sparepart', $uji['servis']), [
            'id_sparepart' => $sparepart->id_sparepart,
            'jumlah' => 2,
        ])
        ->assertSessionHasErrors('jumlah');

    expect(app(StokService::class)->stok($sparepart))->toBe(1);
    $this->assertDatabaseCount('penggunaan_sparepart', 0);
});

it('pemakaian spare part hanya saat servis dikerjakan', function () {
    $uji = tahap5Disetujui();
    $sparepart = tahap4Sparepart();
    app(StokService::class)->catatMasuk($sparepart, 3, 'stok awal', $uji['admin']);

    $this->actingAs($uji['admin'])
        ->post(route('admin.servis.pakai-sparepart', $uji['servis']), [
            'id_sparepart' => $sparepart->id_sparepart,
            'jumlah' => 1,
        ])
        ->assertSessionHasErrors('status');

    expect(app(StokService::class)->stok($sparepart))->toBe(3);
});

it('spare part nonaktif tidak bisa dipakai', function () {
    $uji = tahap5Dikerjakan();
    $sparepart = tahap4Sparepart();
    app(StokService::class)->catatMasuk($sparepart, 3, 'stok awal', $uji['admin']);
    $sparepart->forceFill(['status' => Sparepart::STATUS_NONAKTIF])->save();

    $this->actingAs($uji['teknisi'])
        ->post(route('teknisi.pekerjaan.pakai-sparepart', $uji['servis']), [
            'id_sparepart' => $sparepart->id_sparepart,
            'jumlah' => 1,
        ])
        ->assertSessionHasErrors('id_sparepart');
});

it('penyesuaian stok bisa menambah dan mengurangi dengan alasan', function () {
    $uji = tahap5Dikerjakan();
    $sparepart = tahap4Sparepart();
    app(StokService::class)->catatMasuk($sparepart, 5, 'stok awal', $uji['admin']);

    $this->actingAs($uji['admin'])
        ->post(route('admin.sparepart.penyesuaian', $sparepart), ['jumlah' => -2, 'alasan' => 'stock opname'])
        ->assertSessionHasNoErrors();

    expect(app(StokService::class)->stok($sparepart))->toBe(3);

    $this->actingAs($uji['admin'])
        ->post(route('admin.sparepart.penyesuaian', $sparepart), ['jumlah' => 4, 'alasan' => 'temuan gudang'])
        ->assertSessionHasNoErrors();

    expect(app(StokService::class)->stok($sparepart))->toBe(7);
});

it('penyesuaian tidak boleh membuat stok negatif atau tanpa alasan', function () {
    $uji = tahap5Dikerjakan();
    $sparepart = tahap4Sparepart();
    app(StokService::class)->catatMasuk($sparepart, 2, 'stok awal', $uji['admin']);

    $this->actingAs($uji['admin'])
        ->post(route('admin.sparepart.penyesuaian', $sparepart), ['jumlah' => -3, 'alasan' => 'salah hitung'])
        ->assertSessionHasErrors('jumlah');

    $this->actingAs($uji['admin'])
        ->post(route('admin.sparepart.penyesuaian', $sparepart), ['jumlah' => 1, 'alasan' => ''])
        ->assertSessionHasErrors('alasan');

    expect(app(StokService::class)->stok($sparepart))->toBe(2);
});

it('teknisi tidak bisa menyesuaikan stok', function () {
    $uji = tahap5Dikerjakan();
    $sparepart = tahap4Sparepart();

    $this->actingAs($uji['teknisi'])
        ->post(route('admin.sparepart.penyesuaian', $sparepart), ['jumlah' => 1, 'alasan' => 'coba'])
        ->assertForbidden();
});

it('detail servis menampilkan spare part terpakai dan hasil QC', function () {
    $uji = tahap5Dikerjakan();
    $sparepart = tahap4Sparepart('Baterai OEM');
    app(StokService::class)->catatMasuk($sparepart, 3, 'stok awal', $uji['admin']);

    $this->actingAs($uji['teknisi'])
        ->post(route('teknisi.pekerjaan.pakai-sparepart', $uji['servis']), [
            'id_sparepart' => $sparepart->id_sparepart,
            'jumlah' => 1,
        ]);

    $this->actingAs($uji['teknisi'])
        ->get(route('teknisi.pekerjaan.show', $uji['servis']))
        ->assertOk()
        ->assertSee('Baterai OEM')
        ->assertSee('Quality control');
});
