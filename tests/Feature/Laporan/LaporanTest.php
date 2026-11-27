<?php

use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Services\DiagnosisService;
use App\Services\LaporanService;
use App\Services\PembayaranService;
use App\Services\PengerjaanService;
use App\Services\StokService;
use Illuminate\Support\Facades\Route;

/** Dashboard dan laporan Tahap 7 (fitur 46–51, 53). */
it('menampilkan dashboard admin dengan ringkasan servis', function () {
    $uji = tahap6Lunas();
    $admin = $uji['admin'];

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Servis aktif')
        ->assertSee($uji['servis']->nomor_servis);
});

it('mengalihkan /dashboard sesuai peran', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $teknisi = akunPeranUji(Pengguna::PERAN_TEKNISI);
    $customer = akunPeranUji(Pengguna::PERAN_CUSTOMER);

    $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    $this->actingAs($teknisi)->get('/dashboard')->assertRedirect(route('teknisi.dashboard'));
    $this->actingAs($customer)->get('/dashboard')->assertRedirect(route('pelanggan.dashboard'));
});

it('teknisi hanya melihat servis yang ditugaskan kepadanya', function () {
    $uji = tahap6Lunas();
    $lain = akunPeranUji(Pengguna::PERAN_TEKNISI);

    $this->actingAs($uji['teknisi'])->get(route('teknisi.dashboard'))
        ->assertOk()
        ->assertDontSee($lain->nama);

    $this->actingAs($uji['teknisi'])->get(route('teknisi.laporan.servis'))
        ->assertOk()
        ->assertSee('Laporan servis');
});

it('customer hanya melihat servis miliknya di dashboard', function () {
    $uji = tahap6Lunas();
    $lain = tahap6Lunas();

    $this->actingAs($uji['customer'])->get(route('pelanggan.dashboard'))
        ->assertOk()
        ->assertSee($uji['servis']->nomor_servis)
        ->assertDontSee($lain['servis']->nomor_servis);
});

it('admin dibatasi ke pendapatan hari ini, owner melihat seluruh rentang', function () {
    $uji = tahap6Lunas();

    DB::table('pembayaran')->update(['diputuskan_pada' => now()->subDays(3)]);

    $this->actingAs($uji['admin'])->get(route('admin.laporan.pendapatan'))
        ->assertOk()
        ->assertSee('hari ini')
        ->assertDontSee(rupiah($uji['servis']->totalTagihan()));

    $owner = akunPeranUji(Pengguna::PERAN_OWNER);

    $this->actingAs($owner)->get(route('admin.laporan.pendapatan'))
        ->assertOk()
        ->assertSee(rupiah($uji['servis']->totalTagihan()));
});

it('laporan servis admin tidak dibatasi hari ini (hanya pendapatan yang dibatasi)', function () {
    $uji = tahap5Dikerjakan();
    $uji['servis']->forceFill(['tanggal_masuk' => now()->subDays(20)])->save();

    $res = $this->actingAs($uji['admin'])->get(route('admin.laporan.export', 'servis'))->assertOk();
    expect($res->streamedContent())->toContain('Dikerjakan,1');
});

it('menghitung jumlah servis per status dan servis lewat SLA', function () {
    $uji = tahap5Dikerjakan();
    $laporan = app(LaporanService::class);

    expect($laporan->servisPerStatus()[Servis::DIKERJAKAN])->toBe(1);
    expect($laporan->jumlahTerlambat())->toBe(0);

    $uji['servis']->forceFill(['tanggal_masuk' => now()->subDays(30), 'sla_hari_salinan' => 7])->save();

    expect($laporan->jumlahTerlambat())->toBe(1);
    expect($uji['servis']->refresh()->terlambat())->toBeTrue();

    $this->actingAs($uji['admin'])->get(route('admin.servis.terlambat'))
        ->assertOk()
        ->assertSee($uji['servis']->nomor_servis);
});

it('menghitung pendapatan bersih: bayar dikurangi pembalikan', function () {
    $uji = tahap6Lunas();
    $laporan = app(LaporanService::class);

    expect(array_sum(array_column($laporan->pendapatan(), 'total')))
        ->toBe((float) $uji['servis']->totalTagihan());

    $asal = Pembayaran::where('id_servis', $uji['servis']->id_servis)
        ->where('jenis', Pembayaran::BAYAR)->firstOrFail();

    app(PembayaranService::class)->pembalikan(
        $asal, 50000, 'Kelebihan bayar', $uji['admin'],
    );

    expect(array_sum(array_column($laporan->pendapatan(), 'total')))
        ->toBe((float) $uji['servis']->totalTagihan() - 50000.0);
});

it('menghitung statistik spare part terpakai dan kategori kerusakan', function () {
    $uji = tahap5Dikerjakan();
    $sparepart = tahap4Sparepart('Baterai', 250000);
    app(StokService::class)->catatPenyesuaian($sparepart, 5, 'Stok awal', $uji['admin']);

    app(PengerjaanService::class)
        ->catatPenggunaan($uji['servis'], $sparepart, 2, $uji['teknisi']);

    app(DiagnosisService::class)->catat(
        $uji['servis'], ['kategori_kerusakan' => 'Layar', 'hasil' => 'Panel retak'], $uji['teknisi'],
    );

    $laporan = app(LaporanService::class);
    $baris = $laporan->statistikSparepart()->first();

    expect($baris->nama)->toBe('Baterai')
        ->and($baris->terpakai)->toBe(2)
        ->and($baris->stok)->toBe(3);

    expect($laporan->kerusakanTerbanyak()->first())->toBe(['kategori' => 'layar', 'jumlah' => 1]);

    $this->actingAs($uji['admin'])->get(route('admin.laporan.statistik'))
        ->assertOk()->assertSee('Baterai');
});

it('mengunduh laporan sebagai CSV', function () {
    $uji = tahap6Lunas();

    $res = $this->actingAs($uji['admin'])->get(route('admin.laporan.export', 'servis'));
    $res->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $isi = $res->streamedContent();
    expect($isi)->toContain('status,jumlah')
        ->toContain(Servis::LABEL_STATUS[Servis::SIAP_DIAMBIL]);

    $this->actingAs($uji['admin'])->get(route('admin.laporan.export', 'tidak-ada'))->assertNotFound();
});

it('menampilkan riwayat audit dan menolak peran selain admin/owner', function () {
    $uji = tahap6Lunas();

    $this->actingAs($uji['admin'])->get(route('admin.audit.index'))
        ->assertOk()
        ->assertSee('Riwayat perubahan');

    $this->actingAs($uji['teknisi'])->get(route('admin.audit.index'))->assertForbidden();
    $this->actingAs($uji['customer'])->get(route('admin.audit.index'))->assertForbidden();
});

it('tidak menyediakan rute ubah/hapus untuk audit', function () {
    expect(collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($r) => str_contains($r->uri(), 'audit'))
        ->map(fn ($r) => $r->methods())
        ->flatten()->unique()->values()->all()
    )->toBe(['GET', 'HEAD']);
});
