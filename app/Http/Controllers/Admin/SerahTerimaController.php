<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SerahTerimaRequest;
use App\Models\Pengambilan;
use App\Models\PengaturanBisnis;
use App\Models\Servis;
use App\Services\SerahTerimaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** Serah terima barang (fitur 44) dan bukti pengambilan (fitur 45). */
class SerahTerimaController extends Controller
{
    public function __construct(private readonly SerahTerimaService $serahTerima) {}

    public function store(SerahTerimaRequest $request, Servis $servis): RedirectResponse
    {
        $this->authorize('kelola', $servis);

        $this->serahTerima->serahkan($servis, $request->validated(), $request->user());

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Barang diserahkan, servis selesai.');
    }

    public function bukti(Servis $servis): Response
    {
        $this->authorize('lihat', $servis);

        $pengambilan = $servis->pengambilan()->with('penyerah')->firstOrFail();

        $servis->load(['pelanggan']);

        $qr = QrCode::format('svg')->size(140)->margin(1)
            ->generate(route('publik.lacak.hasil', $servis->kode_tracking));

        return Pdf::loadView('cetak.bukti-pengambilan', [
            'servis' => $servis,
            'pengambilan' => $pengambilan,
            'qr' => $qr,
            'bisnis' => [
                'nama' => PengaturanBisnis::nilai('nama_bisnis', config('app.name')),
                'alamat' => PengaturanBisnis::nilai('alamat_bisnis', ''),
            ],
        ])->stream("bukti-pengambilan-{$servis->nomor_servis}.pdf");
    }
}
