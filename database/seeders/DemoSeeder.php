<?php

namespace Database\Seeders;

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
use App\Services\StokService;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk demo/pelatihan — bukan untuk produksi.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Membuat akun admin/teknisi/customer + beberapa servis pada status berbeda,
 * satu servis lewat SLA, spare part terpakai, dan satu pembayaran lunas.
 * Aman dijalankan dua kali: berhenti bila servis demo sudah ada.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Servis::query()->exists()) {
            $this->command?->warn('Sudah ada servis; DemoSeeder dilewati.');

            return;
        }

        $admin = $this->akun('Admin Demo', 'admin@demo.test', Pengguna::PERAN_ADMIN);
        $teknisi = $this->akun('Teknisi Demo', 'teknisi@demo.test', Pengguna::PERAN_TEKNISI);
        $customer = $this->akun('Customer Demo', 'customer@demo.test', Pengguna::PERAN_CUSTOMER);

        $pelanggan = Pelanggan::create([
            'id_pengguna' => $customer->id_pengguna,
            'nama' => $customer->nama,
            'no_hp' => '081200000001',
            'email' => $customer->email,
            'alamat' => 'Jl. Contoh No. 1',
        ]);

        $tarif = TarifJasa::firstOrCreate(['nama' => 'Pemeriksaan dan pembersihan'], ['harga' => 150000]);
        $sparepart = Sparepart::firstOrCreate(
            ['sku' => 'DEMO-KEYBRD'],
            ['nama' => 'Keyboard laptop 14 inci', 'harga_beli' => 350000, 'harga_jual' => 500000, 'stok_minimum' => 2],
        );

        if (app(StokService::class)->stok($sparepart) < 10) {
            app(StokService::class)->catatPenyesuaian($sparepart, 10, 'Stok awal demo', $admin);
        }

        $servisService = app(ServisService::class);
        $buat = fn (string $seri, string $perangkat, string $keluhan) => $servisService->buatServis([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_teknisi' => $teknisi->id_pengguna,
            'jenis_perangkat' => 'laptop',
            'merek' => 'Lenovo',
            'model' => $perangkat,
            'nomor_seri' => $seri,
            'keluhan' => $keluhan,
            'kondisi_fisik' => 'Baik, lecet halus di tutup',
            'kelengkapan' => 'Unit + charger',
        ], $admin);

        $diagnosis = app(DiagnosisService::class);
        $estimasi = app(EstimasiService::class);
        $pengerjaan = app(PengerjaanService::class);

        // 1) Selesai dikerjakan, lunas tunai, sudah siap diambil.
        $s1 = $buat('DEMO-001', 'ThinkPad T14', 'Keyboard sebagian tidak berfungsi');
        $servisService->pindahStatus($s1, Servis::MENUNGGU_DIAGNOSIS, $admin);
        $diagnosis->catat($s1, ['kategori_kerusakan' => 'keyboard', 'hasil' => 'Keyboard perlu diganti.'], $teknisi);
        $e1 = $estimasi->simpanDraf($s1, [
            ['sumber' => 'jasa:'.$tarif->id_tarif, 'jumlah' => 1],
            ['sumber' => 'sparepart:'.$sparepart->id_sparepart, 'jumlah' => 1],
        ], $admin);
        $estimasi->putuskan($estimasi->kirim($e1, $admin), true, $customer);
        $pengerjaan->ubahStatus($s1->refresh(), Servis::DIKERJAKAN, $teknisi);
        $pengerjaan->catatPenggunaan($s1->refresh(), $sparepart, 1, $teknisi);
        $pengerjaan->ubahStatus($s1->refresh(), Servis::QUALITY_CONTROL, $teknisi);
        $pengerjaan->catatQc($s1->refresh(), array_fill_keys(PengerjaanService::BUTIR_QC, 'lulus'), $teknisi);
        $s1 = $pengerjaan->siapDiambil($s1->refresh(), $teknisi);
        app(PembayaranService::class)->konfirmasi(
            app(PembayaranService::class)->buat($s1, $s1->totalTagihan(), 'tunai', $admin), $admin,
        );

        // 2) Sedang dikerjakan dan sudah lewat SLA.
        $s2 = $buat('DEMO-002', 'IdeaPad 3', 'Mati total setelah kena air');
        $servisService->pindahStatus($s2, Servis::MENUNGGU_DIAGNOSIS, $admin);
        $diagnosis->catat($s2, ['kategori_kerusakan' => 'motherboard', 'hasil' => 'Korosi pada jalur daya.'], $teknisi);
        $e2 = $estimasi->simpanDraf($s2, [['sumber' => 'jasa:'.$tarif->id_tarif, 'jumlah' => 1]], $admin);
        $estimasi->putuskan($estimasi->kirim($e2, $admin), true, $customer);
        $pengerjaan->ubahStatus($s2->refresh(), Servis::DIKERJAKAN, $teknisi);
        $s2->forceFill(['tanggal_masuk' => now()->subDays(12)])->save();

        // 3) Baru diterima, belum didiagnosis.
        $buat('DEMO-003', 'ThinkPad X1', 'Layar berkedip saat dinyalakan');

        $this->command?->info('Demo: admin@demo.test / teknisi@demo.test / customer@demo.test (password: rahasia123)');
    }

    private function akun(string $nama, string $email, string $peran): Pengguna
    {
        $akun = Pengguna::firstOrNew(['email' => $email]);
        $akun->fill(['nama' => $nama, 'password' => 'rahasia123']);
        $akun->forceFill(['peran' => $peran, 'status_akun' => Pengguna::STATUS_AKTIF])->save();

        return $akun;
    }
}
