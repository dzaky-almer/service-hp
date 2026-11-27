<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\PengaturanBisnis;
use Illuminate\View\View;

/**
 * Beranda publik: informasi bisnis + jalur ke pelacakan status (fitur 9, 10).
 */
class BerandaController extends Controller
{
    public function __invoke(): View
    {
        return view('publik.beranda', [
            'namaBisnis' => PengaturanBisnis::nilai('nama_bisnis', config('app.name')),
            'alamatBisnis' => PengaturanBisnis::nilai('alamat_bisnis', ''),
            'slaHari' => (int) PengaturanBisnis::nilai('sla_hari', 7),
            'biayaPemeriksaan' => (float) PengaturanBisnis::nilai('biaya_pemeriksaan', 0),
        ]);
    }
}
