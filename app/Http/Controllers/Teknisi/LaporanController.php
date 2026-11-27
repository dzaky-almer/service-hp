<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Laporan dan statistik pribadi teknisi (fitur 47, 49, 50). Selalu difilter ke dirinya. */
class LaporanController extends Controller
{
    public function __construct(private readonly LaporanService $laporan) {}

    public function laporan(Request $request)
    {
        $filter = $this->filter($request);

        return view('laporan.servis', [
            'filter' => $filter,
            'perStatus' => $this->laporan->servisPerStatus($filter),
            'durasi' => $this->laporan->durasi($filter),
            'terlambat' => $this->laporan->jumlahTerlambat($filter),
            'export' => null,
        ]);
    }

    public function performa(Request $request)
    {
        return view('laporan.performa', [
            'filter' => $this->filter($request),
            'baris' => $this->laporan->performaTeknisi($this->filter($request)),
            'export' => null,
        ]);
    }

    public function statistik(Request $request)
    {
        $filter = $this->filter($request);

        return view('laporan.statistik', [
            'filter' => $filter,
            'sparepart' => $this->laporan->statistikSparepart($filter),
            'kerusakan' => $this->laporan->kerusakanTerbanyak($filter),
            'export' => null,
        ]);
    }

    /** @return array{dari?: Carbon, sampai?: Carbon, id_teknisi: int} */
    private function filter(Request $request): array
    {
        return array_filter([
            'dari' => $request->date('dari')?->startOfDay(),
            'sampai' => $request->date('sampai')?->endOfDay(),
        ]) + ['id_teknisi' => $request->user()->id_pengguna];
    }
}
