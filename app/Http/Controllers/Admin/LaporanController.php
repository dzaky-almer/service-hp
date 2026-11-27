<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan admin/owner (fitur 47–51, 53). Admin dibatasi ke pendapatan hari
 * berjalan (PRD §7 tabel fitur 48: △); Owner melihat seluruh rentang.
 */
class LaporanController extends Controller
{
    private const JENIS = ['servis', 'pendapatan', 'performa', 'statistik'];

    public function __construct(private readonly LaporanService $laporan) {}

    public function servis(Request $request)
    {
        $filter = $this->filter($request);

        return view('laporan.servis', [
            'filter' => $filter,
            'perStatus' => $this->laporan->servisPerStatus($filter),
            'durasi' => $this->laporan->durasi($filter),
            'terlambat' => $this->laporan->jumlahTerlambat($filter),
            'export' => 'servis',
        ]);
    }

    public function pendapatan(Request $request)
    {
        $batasi = $this->batasiHariIni($request);
        $filter = $this->filter($request, $batasi);

        return view('laporan.pendapatan', [
            'filter' => $filter,
            'baris' => $this->laporan->pendapatan($filter, harian: $batasi),
            'batasHariIni' => $batasi,
            'export' => 'pendapatan',
        ]);
    }

    public function performa(Request $request)
    {
        $filter = $this->filter($request);

        return view('laporan.performa', [
            'filter' => $filter,
            'baris' => $this->laporan->performaTeknisi($filter),
            'export' => 'performa',
        ]);
    }

    public function statistik(Request $request)
    {
        $filter = $this->filter($request);

        return view('laporan.statistik', [
            'filter' => $filter,
            'sparepart' => $this->laporan->statistikSparepart($filter),
            'kerusakan' => $this->laporan->kerusakanTerbanyak($filter),
            'export' => 'statistik',
        ]);
    }

    /** Export CSV (fitur 51). */
    public function export(Request $request, string $jenis): StreamedResponse
    {
        abort_unless(in_array($jenis, self::JENIS, true), 404);

        $batasi = $jenis === 'pendapatan' && $this->batasiHariIni($request);
        $filter = $this->filter($request, $batasi);
        $nama = "laporan-{$jenis}-".now()->format('Ymd-His').'.csv';

        [$judul, $baris] = match ($jenis) {
            'servis' => [
                ['status', 'jumlah'],
                collect($this->laporan->servisPerStatus($filter))
                    ->map(fn ($jumlah, $status) => [Servis::LABEL_STATUS[$status] ?? $status, $jumlah])->values()->all(),
            ],
            'pendapatan' => [
                ['periode', 'pendapatan'],
                collect($this->laporan->pendapatan($filter, harian: $batasi))
                    ->map(fn ($b) => [$b['periode'], $b['total']])->all(),
            ],
            'performa' => [
                ['teknisi', 'ditugaskan', 'selesai', 'rata_jam'],
                $this->laporan->performaTeknisi($filter)
                    ->map(fn ($b) => [$b->nama, $b->tugas, $b->selesai, $b->rata_jam ?? ''])->all(),
            ],
            'statistik' => [
                ['sku', 'spare_part', 'terpakai', 'biaya', 'stok'],
                $this->laporan->statistikSparepart($filter)
                    ->map(fn ($b) => [$b->sku, $b->nama, $b->terpakai, $b->biaya, $b->stok])->all(),
            ],
        };

        return response()->streamDownload(function () use ($judul, $baris) {
            $keluar = fopen('php://output', 'w');
            fputcsv($keluar, $judul);
            foreach ($baris as $satu) {
                fputcsv($keluar, $satu);
            }
            fclose($keluar);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{dari?: Carbon, sampai?: Carbon, id_teknisi?: int, status?: string} */
    private function filter(Request $request, ?bool $batasiHariIni = null): array
    {
        $filter = array_filter([
            'status' => $request->string('status')->value() ?: null,
            'id_teknisi' => $request->integer('id_teknisi') ?: null,
        ], fn ($v) => $v !== null);

        if ($batasiHariIni ?? false) {
            return ['dari' => now()->startOfDay(), 'sampai' => now()] + $filter;
        }

        return array_filter([
            'dari' => $request->date('dari')?->startOfDay(),
            'sampai' => $request->date('sampai')?->endOfDay(),
        ]) + $filter;
    }

    private function batasiHariIni(Request $request): bool
    {
        return $request->user()->peran === Pengguna::PERAN_ADMIN;
    }
}
