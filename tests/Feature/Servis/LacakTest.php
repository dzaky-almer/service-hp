<?php

use App\Models\Pelanggan;
use App\Models\PengaturanBisnis;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Services\ServisService;

/*
| Tahap 3 — halaman publik (fitur 9, 10): info bisnis di beranda dan pelacakan
| status tanpa login. BR-14: halaman lacak tidak boleh membocorkan data pelanggan.
*/

function servisPublikUji(): Servis
{
    $admin = Pengguna::factory()->create(['peran' => Pengguna::PERAN_ADMIN]);
    $pelanggan = Pelanggan::create([
        'nama' => 'Rahasia Pelanggan',
        'no_hp' => '081399999999',
        'email' => 'rahasia@example.com',
        'alamat' => 'Jl. Rahasia 1',
    ]);

    return app(ServisService::class)->buatServis([
        'id_pelanggan' => $pelanggan->id_pelanggan,
        'jenis_perangkat' => 'hp',
        'merek' => 'Samsung',
        'model' => 'A54',
        'nomor_seri' => null,
        'keluhan' => 'Layar retak',
        'kondisi_fisik' => null,
        'kelengkapan' => null,
    ], $admin);
}

test('beranda publik menampilkan informasi bisnis', function () {
    PengaturanBisnis::updateOrCreate(['kunci' => 'nama_bisnis'], ['nilai' => 'Bengkel Servis Uji']);
    PengaturanBisnis::updateOrCreate(['kunci' => 'alamat_bisnis'], ['nilai' => 'Jl. Uji 2']);

    $this->get(route('publik.beranda'))
        ->assertOk()
        ->assertSee('Bengkel Servis Uji')
        ->assertSee('Jl. Uji 2');
});

test('halaman lacak tersedia tanpa login', function () {
    $this->get(route('publik.lacak.index'))->assertOk()->assertSee('kode tracking');
});

test('kode tracking salah ditolak dengan pesan', function () {
    $this->from(route('publik.lacak.index'))
        ->post(route('publik.lacak.cari'), ['kode_tracking' => 'ZZZZZZZZZZZZ'])
        ->assertRedirect(route('publik.lacak.index'))
        ->assertSessionHasErrors('kode_tracking');

    expect(session('errors')->first('kode_tracking'))->toContain('tidak ditemukan');
});

test('kode tracking benar menampilkan status tanpa data pelanggan', function () {
    $servis = servisPublikUji();

    $this->post(route('publik.lacak.cari'), ['kode_tracking' => strtolower($servis->kode_tracking)])
        ->assertRedirect(route('publik.lacak.hasil', $servis->kode_tracking));

    $this->get(route('publik.lacak.hasil', $servis->kode_tracking))
        ->assertOk()
        ->assertSee($servis->nomor_servis)
        ->assertSee('Samsung A54')
        ->assertSee('Diterima')
        ->assertDontSee('Rahasia Pelanggan')
        ->assertDontSee('081399999999')
        ->assertDontSee('Layar retak');
});

test('kode tracking tidak dikenal menghasilkan 404', function () {
    $this->get(route('publik.lacak.hasil', 'ABCDEFGHJKLM'))->assertNotFound();
});
