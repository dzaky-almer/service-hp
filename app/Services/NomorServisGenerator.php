<?php

namespace App\Services;

use App\Models\Servis;
use Illuminate\Support\Facades\DB;

/**
 * Pembuat nomor servis berurutan per tahun + kode tracking acak (BR-15, ARCHITECTURE §3.4).
 *
 * Nomor dibentuk di dalam transaksi yang mengunci baris `urutan_nomor` tahun berjalan,
 * jadi dua permintaan bersamaan tidak mendapat nomor yang sama.
 */
class NomorServisGenerator
{
    /** Tanpa 0/O/1/I/L agar tidak salah baca saat diketik pelanggan. */
    private const ALFABET_TRACKING = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    private const PANJANG_TRACKING = 12;

    /**
     * @return array{nomor_servis: string, kode_tracking: string}
     */
    public function berikutnya(): array
    {
        return DB::transaction(function (): array {
            $tahun = (int) now()->format('Y');

            DB::table('urutan_nomor')->insertOrIgnore(['tahun' => $tahun, 'terakhir' => 0]);
            $terakhir = (int) DB::table('urutan_nomor')
                ->where('tahun', $tahun)
                ->lockForUpdate()
                ->value('terakhir');

            $urut = $terakhir + 1;
            DB::table('urutan_nomor')->where('tahun', $tahun)->update(['terakhir' => $urut]);

            return [
                'nomor_servis' => sprintf('SRV-%d-%05d', $tahun, $urut),
                'kode_tracking' => $this->kodeTracking(),
            ];
        });
    }

    /** Ulangi bila bentrok dengan kode yang sudah ada (peluangnya sangat kecil). */
    private function kodeTracking(): string
    {
        do {
            $kode = '';
            for ($i = 0; $i < self::PANJANG_TRACKING; $i++) {
                $kode .= self::ALFABET_TRACKING[random_int(0, strlen(self::ALFABET_TRACKING) - 1)];
            }
        } while (Servis::where('kode_tracking', $kode)->exists());

        return $kode;
    }
}
