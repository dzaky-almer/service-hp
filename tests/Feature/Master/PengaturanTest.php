<?php

use App\Models\Pengguna;
use App\Models\TarifJasa;
use Illuminate\Support\Facades\DB;

/*
| Tahap 2 — konfigurasi bisnis dan tarif jasa (fitur 52, khusus Owner).
*/

function ownerPengaturanUji(): Pengguna
{
    return Pengguna::factory()->create(['peran' => Pengguna::PERAN_OWNER]);
}

function dataPengaturanUji(array $ubah = []): array
{
    return $ubah + [
        'nama_bisnis' => 'Servis Uji',
        'alamat_bisnis' => 'Jl. Uji 2',
        'sla_hari' => 5,
        'biaya_pemeriksaan' => 25000,
    ];
}

test('owner melihat dan menyimpan pengaturan bisnis', function () {
    $owner = ownerPengaturanUji();

    $this->actingAs($owner)->get(route('admin.pengaturan.edit'))->assertOk();

    $this->actingAs($owner)
        ->put(route('admin.pengaturan.update'), dataPengaturanUji())
        ->assertRedirect(route('admin.pengaturan.edit'))
        ->assertSessionHasNoErrors();

    expect(DB::table('pengaturan_bisnis')->where('kunci', 'sla_hari')->value('nilai'))->toBe('5')
        ->and(DB::table('pengaturan_bisnis')->where('kunci', 'biaya_pemeriksaan')->value('nilai'))->toBe('25000')
        ->and(DB::table('audit_log')->where('aksi', 'ubah_pengaturan')->count())->toBe(1);
});

test('pengaturan bisnis ditolak untuk admin, customer, dan tamu', function () {
    $admin = Pengguna::factory()->create(['peran' => Pengguna::PERAN_ADMIN]);

    $this->actingAs($admin)->get(route('admin.pengaturan.edit'))->assertForbidden();
    $this->actingAs($admin)->put(route('admin.pengaturan.update'), dataPengaturanUji())->assertForbidden();

    $customer = Pengguna::factory()->create(['peran' => Pengguna::PERAN_CUSTOMER]);
    $this->actingAs($customer)->get(route('admin.pengaturan.edit'))->assertForbidden();

    auth()->logout();
    $this->get(route('admin.pengaturan.edit'))->assertRedirect(route('login'));
});

test('SLA dan biaya pemeriksaan divalidasi', function () {
    $owner = ownerPengaturanUji();

    $this->actingAs($owner)
        ->put(route('admin.pengaturan.update'), dataPengaturanUji([
            'nama_bisnis' => '',
            'sla_hari' => 0,
            'biaya_pemeriksaan' => -1,
        ]))
        ->assertSessionHasErrors(['nama_bisnis', 'sla_hari', 'biaya_pemeriksaan']);

    expect(DB::table('audit_log')->where('aksi', 'ubah_pengaturan')->count())->toBe(0);
});

test('owner mengelola tarif jasa', function () {
    $owner = ownerPengaturanUji();

    $this->actingAs($owner)
        ->post(route('admin.tarif-jasa.store'), ['nama' => 'Ganti LCD', 'harga' => 150000])
        ->assertRedirect(route('admin.tarif-jasa.index'));

    $tarif = TarifJasa::firstWhere('nama', 'Ganti LCD');

    $this->actingAs($owner)
        ->put(route('admin.tarif-jasa.update', $tarif), [
            'nama' => 'Ganti LCD',
            'harga' => 160000,
            'status' => TarifJasa::STATUS_NONAKTIF,
        ])
        ->assertRedirect(route('admin.tarif-jasa.index'));

    $tarif->refresh();

    expect((float) $tarif->harga)->toBe(160000.0)
        ->and($tarif->status)->toBe(TarifJasa::STATUS_NONAKTIF)
        ->and(DB::table('audit_log')->where('aksi', 'buat_tarif_jasa')->count())->toBe(1)
        ->and(DB::table('audit_log')->where('aksi', 'ubah_tarif_jasa')->count())->toBe(1);
});

test('tarif jasa hanya untuk owner', function () {
    $admin = Pengguna::factory()->create(['peran' => Pengguna::PERAN_ADMIN]);

    $this->actingAs($admin)->get(route('admin.tarif-jasa.index'))->assertForbidden();
    $this->actingAs($admin)
        ->post(route('admin.tarif-jasa.store'), ['nama' => 'X', 'harga' => 1])
        ->assertForbidden();

    expect(TarifJasa::count())->toBe(0);
});

test('tarif jasa yang belum dipakai boleh dihapus, yang sudah dipakai ditolak', function () {
    $owner = ownerPengaturanUji();

    $this->actingAs($owner)->post(route('admin.tarif-jasa.store'), ['nama' => 'Bebas', 'harga' => 10000]);
    $this->actingAs($owner)->post(route('admin.tarif-jasa.store'), ['nama' => 'Dipakai', 'harga' => 20000]);

    $bebas = TarifJasa::firstWhere('nama', 'Bebas');
    $dipakai = TarifJasa::firstWhere('nama', 'Dipakai');

    // Rantai FK minimal agar tarif benar-benar terpakai di item estimasi.
    $idPelanggan = DB::table('pelanggan')->insertGetId([
        'nama' => 'Pelanggan Uji',
        'no_hp' => '081399990001',
        'dibuat_pada' => now(),
    ]);
    $idServis = DB::table('servis')->insertGetId([
        'nomor_servis' => 'S-0001',
        'kode_tracking' => 'ABCDEF123456',
        'id_pelanggan' => $idPelanggan,
        'jenis_perangkat' => 'hp',
        'merek' => 'Uji',
        'model' => 'Uji',
        'keluhan' => 'Uji',
        'sla_hari_salinan' => 7,
        'tanggal_masuk' => now(),
        'dibuat_oleh' => $owner->id_pengguna,
        'dibuat_pada' => now(),
        'diperbarui_pada' => now(),
    ]);
    $idEstimasi = DB::table('estimasi')->insertGetId([
        'id_servis' => $idServis,
        'versi' => 1,
        'status' => 'draf',
        'dibuat_oleh' => $owner->id_pengguna,
        'dibuat_pada' => now(),
    ]);
    DB::table('item_estimasi')->insert([
        'id_estimasi' => $idEstimasi,
        'jenis' => 'jasa',
        'deskripsi' => 'Jasa dipakai',
        'id_tarif' => $dipakai->id_tarif,
        'jumlah' => 1,
        'harga_satuan_salinan' => 20000,
    ]);

    $this->actingAs($owner)
        ->delete(route('admin.tarif-jasa.destroy', $bebas))
        ->assertRedirect(route('admin.tarif-jasa.index'));

    expect(TarifJasa::whereKey($bebas->id_tarif)->exists())->toBeFalse();

    $this->actingAs($owner)
        ->delete(route('admin.tarif-jasa.destroy', $dipakai))
        ->assertSessionHasErrors('tarif');

    expect(TarifJasa::whereKey($dipakai->id_tarif)->exists())->toBeTrue()
        ->and(DB::table('audit_log')->where('aksi', 'hapus_tarif_jasa')->count())->toBe(1);
});
