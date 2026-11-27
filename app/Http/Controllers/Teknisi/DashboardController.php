<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use App\Models\Servis;
use App\Services\LaporanService;
use Illuminate\Http\Request;

/** Dashboard teknisi (fitur 46, 54): hanya servis yang ditugaskan kepadanya. */
class DashboardController extends Controller
{
    public function __invoke(Request $request, LaporanService $laporan)
    {
        $id = $request->user()->id_pengguna;

        return view('teknisi.dashboard', [
            'ringkasan' => $laporan->ringkasan($id),
            'tugas' => Servis::where('id_teknisi', $id)
                ->whereIn('status', [Servis::DISETUJUI, Servis::DIKERJAKAN, Servis::QUALITY_CONTROL])
                ->with('pelanggan')->orderBy('tanggal_masuk')->get(),
            'terlambat' => Servis::where('id_teknisi', $id)->terlambat()->with('pelanggan')->orderBy('tanggal_masuk')->get(),
        ]);
    }
}
