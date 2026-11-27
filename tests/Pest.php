<?php

use App\Models\Estimasi;
use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Models\Sparepart;
use App\Models\TarifJasa;
use App\Services\DiagnosisService;
use App\Services\EstimasiService;
use App\Services\PembayaranService;
use App\Services\PengerjaanService;
use App\Services\ServisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function akunPeranUji(string $peran): Pengguna
{
    return Pengguna::factory()->create(['peran' => $peran]);
}

/** Pelanggan uji; akun login-nya dibuat kalau belum diberikan. */
function tahap4Pelanggan(?Pengguna $akun = null): Pelanggan
{
    $akun ??= akunPeranUji(Pengguna::PERAN_CUSTOMER);

    return Pelanggan::create([
        'id_pengguna' => $akun->id_pengguna,
        'nama' => 'Sari Pelanggan',
        'no_hp' => '0813'.random_int(1000000, 9999999),
        'email' => 'sari'.random_int(1000, 9999).'@example.com',
        'alamat' => 'Jl. Melati 9',
    ]);
}

/** Servis baru (status diterima), opsional langsung masuk tahap diagnosis. */
function tahap4Servis(
    Pengguna $admin,
    Pelanggan $pelanggan,
    ?Pengguna $teknisi = null,
    bool $masukDiagnosis = true,
): Servis {
    $service = app(ServisService::class);

    $servis = $service->buatServis([
        'id_pelanggan' => $pelanggan->id_pelanggan,
        'id_teknisi' => $teknisi?->id_pengguna,
        'jenis_perangkat' => 'laptop',
        'merek' => 'Asus',
        'model' => 'VivoBook 14',
        'nomor_seri' => 'SN-'.random_int(100, 999),
        'keluhan' => 'Mati total setelah kena air',
        'kondisi_fisik' => 'Bodi penyok sudut kiri',
        'kelengkapan' => 'Charger',
    ], $admin);

    if ($masukDiagnosis && $servis->status === Servis::DITERIMA) {
        $servis = $service->pindahStatus($servis, Servis::MENUNGGU_DIAGNOSIS, $admin);
    }

    return $servis->refresh();
}

function tahap4Tarif(string $nama = 'Ganti LCD', float $harga = 350000): TarifJasa
{
    return TarifJasa::create(['nama' => $nama, 'harga' => $harga]);
}

function tahap4Sparepart(string $nama = 'LCD 14 inci', float $hargaJual = 750000): Sparepart
{
    return Sparepart::create([
        'sku' => 'SP-'.random_int(10000, 99999),
        'nama' => $nama,
        'harga_beli' => $hargaJual * 0.7,
        'harga_jual' => $hargaJual,
        'stok_minimum' => 0,
    ]);
}

/** Catat diagnosis, susun draf satu item jasa, lalu kirim: servis jadi menunggu_persetujuan. */
function tahap4EstimasiTerkirim(Servis $servis, Pengguna $admin, float $hargaJasa = 350000): Estimasi
{
    app(DiagnosisService::class)->catat($servis, [
        'kategori_kerusakan' => 'layar',
        'hasil' => 'Panel LCD retak, perlu diganti.',
    ], $admin);

    $tarif = tahap4Tarif('Ganti LCD', $hargaJasa);

    $draf = app(EstimasiService::class)->simpanDraf($servis, [
        ['sumber' => 'jasa:'.$tarif->id_tarif, 'jumlah' => 1],
    ], $admin);

    return app(EstimasiService::class)->kirim($draf, $admin);
}

/** Servis siap dikerjakan: estimasi disetujui customer (status disetujui). */
function tahap5Disetujui(?Pengguna $admin = null, ?Pengguna $teknisi = null, ?Pengguna $customer = null): array
{
    $admin ??= akunPeranUji(Pengguna::PERAN_ADMIN);
    $teknisi ??= akunPeranUji(Pengguna::PERAN_TEKNISI);
    $customer ??= akunPeranUji(Pengguna::PERAN_CUSTOMER);
    $pelanggan = tahap4Pelanggan($customer);
    $servis = tahap4Servis($admin, $pelanggan, $teknisi);
    $estimasi = tahap4EstimasiTerkirim($servis, $admin);

    app(EstimasiService::class)->putuskan($estimasi, true, $customer);

    return compact('admin', 'teknisi', 'customer', 'pelanggan', 'servis');
}

/** Servis di tahap pengerjaan (disetujui -> dikerjakan). */
function tahap5Dikerjakan(): array
{
    $uji = tahap5Disetujui();
    $uji['servis'] = app(ServisService::class)->pindahStatus($uji['servis']->refresh(), Servis::DIKERJAKAN, $uji['teknisi']);

    return $uji;
}

/** Servis siap diambil: dikerjakan -> QC lulus -> siap diambil. */
function tahap6SiapDiambil(): array
{
    $uji = tahap5Dikerjakan();
    $pengerjaan = app(PengerjaanService::class);

    $uji['servis'] = $pengerjaan->ubahStatus($uji['servis']->refresh(), Servis::QUALITY_CONTROL, $uji['teknisi']);
    $pengerjaan->catatQc(
        $uji['servis']->refresh(),
        array_fill_keys(PengerjaanService::BUTIR_QC, 'lulus'),
        $uji['teknisi'],
    );
    $uji['servis'] = $pengerjaan->siapDiambil($uji['servis']->refresh(), $uji['teknisi']);

    return $uji;
}

/** Servis siap diambil dan lunas penuh (tunai dikonfirmasi Admin). */
function tahap6Lunas(?float $nominal = null): array
{
    $uji = tahap6SiapDiambil();
    $pembayaran = app(PembayaranService::class);

    $bayar = $pembayaran->buat($uji['servis'], $nominal ?? $uji['servis']->totalTagihan(), 'tunai', $uji['admin']);
    $pembayaran->konfirmasi($bayar, $uji['admin']);

    return $uji;
}
