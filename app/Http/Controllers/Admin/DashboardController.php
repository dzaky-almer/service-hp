<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Services\LaporanService;
use Illuminate\Http\Request;

/** Dashboard admin/owner (fitur 46, 54). */
class DashboardController extends Controller
{
    public function __invoke(Request $request, LaporanService $laporan)
    {
        $ringkasan = $laporan->ringkasan();

        return view('admin.dashboard', [
            'ringkasan' => $ringkasan,
            'terlambat' => Servis::query()->terlambat()->with('pelanggan', 'teknisi')
                ->orderBy('tanggal_masuk')->limit(10)->get(),
            'terbaru' => Servis::with('pelanggan', 'teknisi')->latest('id_servis')->limit(8)->get(),
            'pembayaranMenunggu' => Pembayaran::where('status', Pembayaran::MENUNGGU)->count(),
            'batasHariIni' => $request->user()->peran === Pengguna::PERAN_ADMIN,
        ]);
    }
}
