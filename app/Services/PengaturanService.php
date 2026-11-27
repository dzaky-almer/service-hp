<?php

namespace App\Services;

use App\Models\PengaturanBisnis;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

class PengaturanService
{
    public function __construct(private AuditService $audit) {}

    /**
     * Simpan konfigurasi bisnis (fitur 52). Satu baris audit per penyimpanan;
     * `pengaturan_bisnis` tidak punya id angka, objek_id audit diisi 0.
     *
     * @param  array<string, string|int|float>  $data
     */
    public function simpan(array $data, Pengguna $pelaku): void
    {
        DB::transaction(function () use ($data, $pelaku) {
            $sebelum = PengaturanBisnis::semua();

            foreach ($data as $kunci => $nilai) {
                PengaturanBisnis::updateOrCreate(['kunci' => $kunci], ['nilai' => (string) $nilai]);
            }

            $objek = new PengaturanBisnis;
            $objek->kunci = '0';
            $this->audit->catat('ubah_pengaturan', $objek, $sebelum, PengaturanBisnis::semua(), $pelaku);
        });
    }
}
