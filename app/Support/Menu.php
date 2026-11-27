<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Item navigasi per peran, mengikuti peta route di ARCHITECTURE.md §4.
 *
 * ponytail: nama route di sini adalah kontrak dengan Tahap 3+. Item yang route-nya
 * belum terdaftar otomatis disembunyikan, jadi navbar tidak error saat route dibuat
 * bertahap. Kalau nama route final berbeda, cukup ubah tabel di `daftar()`.
 */
final class Menu
{
    /**
     * @param  string|null  $peran  null = tamu; '-' = login tapi peran belum dikenali
     * @return list<array{label: string, route: string, aktif: string}>
     */
    public static function untuk(?string $peran): array
    {
        // Route yang didaftarkan setelah boot (mis. di test) belum ada di nameList.
        Route::getRoutes()->refreshNameLookups();

        $item = array_map(
            fn (array $baris): array => [
                'label' => $baris[0],
                'route' => $baris[1],
                'aktif' => $baris[2] ?? $baris[1],
            ],
            self::daftar($peran),
        );

        return array_values(array_filter($item, fn (array $i): bool => Route::has($i['route'])));
    }

    /**
     * @return list<array{0: string, 1: string, 2?: string}>
     */
    private static function daftar(?string $peran): array
    {
        $publik = [
            ['Beranda', 'publik.beranda'],
            ['Lacak Servis', 'publik.lacak.index', 'publik.lacak.*'],
        ];

        return match ($peran) {
            'customer' => [
                ['Dashboard', 'pelanggan.dashboard'],
                ['Servis Saya', 'pelanggan.servis.index', 'pelanggan.servis.*'],
                ...$publik,
            ],
            'teknisi' => [
                ['Dashboard', 'teknisi.dashboard'],
                ['Pekerjaan', 'teknisi.pekerjaan.index', 'teknisi.pekerjaan.*'],
                ['Spare Part', 'teknisi.sparepart.index'],
                ['Laporan', 'teknisi.laporan.servis', 'teknisi.laporan.*'],
            ],
            'admin', 'owner' => [
                ['Dashboard', 'admin.dashboard'],
                ['Servis', 'admin.servis.index', 'admin.servis.*'],
                ['Pekerjaan', 'admin.pekerjaan.index', 'admin.pekerjaan.*'],
                ['Pelanggan', 'admin.pelanggan.index', 'admin.pelanggan.*'],
                ['Spare Part', 'admin.sparepart.index'],
                ['Laporan', 'admin.laporan.servis', 'admin.laporan.*'],
                ['Audit', 'admin.audit.index', 'admin.audit.*'],
                ...($peran === 'owner' ? [
                    ['Staf', 'admin.staf.index', 'admin.staf.*'],
                    ['Pengaturan', 'admin.pengaturan.edit'],
                    ['Tarif Jasa', 'admin.tarif-jasa.index'],
                ] : []),
            ],
            null => [
                ...$publik,
                ['Masuk', 'login'],
                ['Daftar', 'register'],
            ],
            default => [
                ['Dashboard', 'dashboard'],
            ],
        };
    }
}
