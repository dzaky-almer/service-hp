<?php

use App\Models\MutasiStok;
use App\Models\Pengguna;
use App\Models\Sparepart;
use App\Services\StokService;
use Illuminate\Support\Facades\DB;

/*
| Tahap 2 — spare part + mutasi stok masuk (fitur 35, 36), dan larangan hapus
| spare part yang sudah dipakai. Stok selalu dihitung dari `mutasi_stok`.
*/

function dataSparepartUji(array $ubah = []): array
{
    return $ubah + [
        'sku' => 'LCD-01',
        'nama' => 'LCD uji',
        'harga_beli' => 100000,
        'harga_jual' => 150000,
        'stok_minimum' => 2,
        'stok_awal' => 5,
    ];
}

function adminSparepartUji(): Pengguna
{
    return Pengguna::factory()->create(['peran' => Pengguna::PERAN_ADMIN]);
}

function ownerSparepartUji(): Pengguna
{
    return Pengguna::factory()->create(['peran' => Pengguna::PERAN_OWNER]);
}

test('admin membuat spare part dengan stok awal sebagai mutasi masuk', function () {
    $admin = adminSparepartUji();

    $this->actingAs($admin)
        ->post(route('admin.sparepart.store'), dataSparepartUji())
        ->assertRedirect(route('admin.sparepart.index'))
        ->assertSessionHasNoErrors();

    $sparepart = Sparepart::firstWhere('sku', 'LCD-01');

    expect($sparepart->status)->toBe(Sparepart::STATUS_AKTIF)
        ->and((float) $sparepart->harga_jual)->toBe(150000.0)
        ->and(MutasiStok::where('id_sparepart', $sparepart->id_sparepart)->count())->toBe(1)
        ->and(app(StokService::class)->stok($sparepart))->toBe(5)
        ->and(DB::table('audit_log')->where('aksi', 'buat_sparepart')->count())->toBe(1)
        ->and(DB::table('audit_log')->where('aksi', 'stok_masuk')->count())->toBe(1);
});

test('stok dihitung dari seluruh mutasi, bukan kolom tersimpan', function () {
    $admin = adminSparepartUji();
    $this->actingAs($admin)->post(route('admin.sparepart.store'), dataSparepartUji());

    $sparepart = Sparepart::firstWhere('sku', 'LCD-01');

    DB::table('mutasi_stok')->insert([
        'id_sparepart' => $sparepart->id_sparepart,
        'jenis' => MutasiStok::JENIS_KELUAR_SERVIS,
        'jumlah' => -2,
        'alasan' => null,
        'dibuat_oleh' => $admin->id_pengguna,
        'waktu' => now(),
    ]);

    expect(app(StokService::class)->stok($sparepart->id_sparepart))->toBe(3);
});

test('mengubah harga dan menonaktifkan spare part tidak menambah mutasi', function () {
    $admin = adminSparepartUji();
    $this->actingAs($admin)->post(route('admin.sparepart.store'), dataSparepartUji());
    $sparepart = Sparepart::firstWhere('sku', 'LCD-01');

    $this->actingAs($admin)
        ->put(route('admin.sparepart.update', $sparepart), dataSparepartUji([
            'harga_jual' => 175000,
            'status' => Sparepart::STATUS_NONAKTIF,
        ]))
        ->assertRedirect(route('admin.sparepart.index'));

    $sparepart->refresh();

    expect((float) $sparepart->harga_jual)->toBe(175000.0)
        ->and($sparepart->status)->toBe(Sparepart::STATUS_NONAKTIF)
        ->and(MutasiStok::where('id_sparepart', $sparepart->id_sparepart)->count())->toBe(1)
        ->and(DB::table('audit_log')->where('aksi', 'ubah_sparepart')->count())->toBe(1);
});

test('harga negatif, stok negatif, dan SKU ganda ditolak', function () {
    $admin = adminSparepartUji();
    $this->actingAs($admin)->post(route('admin.sparepart.store'), dataSparepartUji());

    $this->actingAs($admin)
        ->post(route('admin.sparepart.store'), dataSparepartUji([
            'sku' => 'LCD-02',
            'harga_beli' => -1,
            'stok_minimum' => -2,
            'stok_awal' => -3,
        ]))
        ->assertSessionHasErrors(['harga_beli', 'stok_minimum', 'stok_awal']);

    $this->actingAs($admin)
        ->post(route('admin.sparepart.store'), dataSparepartUji())
        ->assertSessionHasErrors('sku');

    expect(Sparepart::count())->toBe(1);
});

test('spare part yang belum pernah dipakai boleh dihapus', function () {
    $admin = adminSparepartUji();
    $this->actingAs($admin)->post(route('admin.sparepart.store'), dataSparepartUji(['stok_awal' => 0]));
    $sparepart = Sparepart::firstWhere('sku', 'LCD-01');

    $this->actingAs($admin)
        ->delete(route('admin.sparepart.destroy', $sparepart))
        ->assertRedirect(route('admin.sparepart.index'));

    expect(Sparepart::count())->toBe(0)
        ->and(DB::table('audit_log')->where('aksi', 'hapus_sparepart')->count())->toBe(1);
});

test('spare part yang sudah dipakai ditolak saat dihapus', function () {
    $admin = adminSparepartUji();
    $this->actingAs($admin)->post(route('admin.sparepart.store'), dataSparepartUji());
    $sparepart = Sparepart::firstWhere('sku', 'LCD-01');

    $this->actingAs($admin)
        ->delete(route('admin.sparepart.destroy', $sparepart))
        ->assertSessionHasErrors('sparepart');

    expect(Sparepart::count())->toBe(1)
        ->and(MutasiStok::count())->toBe(1)
        ->and(DB::table('audit_log')->where('aksi', 'hapus_sparepart')->count())->toBe(0);
});

test('admin dan owner melihat daftar spare part, peran lain ditolak', function () {
    $this->actingAs(adminSparepartUji())->get(route('admin.sparepart.index'))->assertOk();
    $this->actingAs(ownerSparepartUji())->get(route('admin.sparepart.index'))->assertOk();

    $this->actingAs(Pengguna::factory()->create(['peran' => Pengguna::PERAN_TEKNISI]))
        ->get(route('admin.sparepart.index'))
        ->assertForbidden();
    $this->actingAs(Pengguna::factory()->create(['peran' => Pengguna::PERAN_CUSTOMER]))
        ->get(route('admin.sparepart.index'))
        ->assertForbidden();
});

test('detail spare part menampilkan stok hasil mutasi', function () {
    $admin = adminSparepartUji();
    $this->actingAs($admin)->post(route('admin.sparepart.store'), dataSparepartUji());
    $sparepart = Sparepart::firstWhere('sku', 'LCD-01');

    $this->actingAs($admin)
        ->get(route('admin.sparepart.show', $sparepart))
        ->assertOk()
        ->assertSee('LCD-01')
        ->assertSee('Stok awal')
        ->assertSee('+5');
});
