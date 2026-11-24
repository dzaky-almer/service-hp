<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $sekarang = now();
        $emailOwner = config('servis.owner_email');

        DB::table('pengguna')->updateOrInsert(
            ['email' => $emailOwner],
            [
                'nama' => 'Owner',
                'password' => Hash::make(config('servis.owner_password')),
                'peran' => 'owner',
                'status_akun' => 'aktif',
                'dibuat_pada' => $sekarang,
                'diperbarui_pada' => $sekarang,
            ],
        );

        $idOwner = DB::table('pengguna')->where('email', $emailOwner)->value('id_pengguna');

        foreach (config('servis.pengaturan_awal') as $kunci => $nilai) {
            DB::table('pengaturan_bisnis')->updateOrInsert(['kunci' => $kunci], ['nilai' => $nilai]);
        }

        $tarifJasa = [
            ['nama' => 'Pemeriksaan dan diagnosa', 'harga' => 50000],
            ['nama' => 'Ganti LCD', 'harga' => 150000],
            ['nama' => 'Pemasangan ulang sistem operasi', 'harga' => 200000],
            ['nama' => 'Pembersihan dan servis ringan', 'harga' => 100000],
        ];

        foreach ($tarifJasa as $tarif) {
            DB::table('tarif_jasa')->updateOrInsert(
                ['nama' => $tarif['nama']],
                ['harga' => $tarif['harga'], 'status' => 'aktif'],
            );
        }

        $sparepart = [
            ['sku' => 'LCD-IP11', 'nama' => 'LCD iPhone 11', 'harga_beli' => 450000, 'harga_jual' => 650000, 'stok_minimum' => 2, 'stok_awal' => 5],
            ['sku' => 'BAT-IP11', 'nama' => 'Baterai iPhone 11', 'harga_beli' => 180000, 'harga_jual' => 275000, 'stok_minimum' => 3, 'stok_awal' => 8],
            ['sku' => 'KBD-LP14', 'nama' => 'Keyboard Laptop 14 inci', 'harga_beli' => 210000, 'harga_jual' => 330000, 'stok_minimum' => 2, 'stok_awal' => 4],
            ['sku' => 'PST-TP01', 'nama' => 'Pasta termal', 'harga_beli' => 25000, 'harga_jual' => 45000, 'stok_minimum' => 5, 'stok_awal' => 20],
        ];

        foreach ($sparepart as $data) {
            $stokAwal = $data['stok_awal'];
            unset($data['stok_awal']);

            DB::table('sparepart')->updateOrInsert(
                ['sku' => $data['sku']],
                $data + ['status' => 'aktif'],
            );

            $idSparepart = DB::table('sparepart')->where('sku', $data['sku'])->value('id_sparepart');

            if (DB::table('mutasi_stok')->where('id_sparepart', $idSparepart)->where('jenis', 'masuk')->doesntExist()) {
                DB::table('mutasi_stok')->insert([
                    'id_sparepart' => $idSparepart,
                    'jenis' => 'masuk',
                    'jumlah' => $stokAwal,
                    'alasan' => 'Stok awal (seed)',
                    'dibuat_oleh' => $idOwner,
                    'waktu' => $sekarang,
                ]);
            }
        }
    }
}
