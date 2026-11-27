<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FotoServisRequest;
use App\Models\Estimasi;
use App\Models\Servis;
use App\Services\EstimasiService;
use App\Services\ServisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Area pelanggan: daftar servis miliknya, detail + riwayat, unggah foto masuk (fitur 28–30).
 */
class ServisController extends Controller
{
    public function __construct(private readonly ServisService $servis) {}

    public function index(Request $request): View
    {
        $daftar = Servis::query()
            ->whereHas('pelanggan', fn ($q) => $q->where('id_pengguna', $request->user()->id_pengguna))
            ->with(['pelanggan', 'teknisi'])
            ->orderByDesc('id_servis')
            ->paginate(15);

        return view('pelanggan.servis.index', compact('daftar'));
    }

    public function show(Servis $servis): View
    {
        $this->authorize('lihat', $servis);

        $servis->load([
            'pelanggan', 'teknisi', 'riwayat.diubahOleh', 'foto.diunggahOleh',
            'diagnosis.pembuat', 'estimasi.item',
        ]);

        return view('pelanggan.servis.show', compact('servis'));
    }

    public function foto(FotoServisRequest $request, Servis $servis): RedirectResponse
    {
        $this->authorize('lihat', $servis);

        $this->servis->tambahFoto($servis, $request->file('foto'), $request->validated('jenis'), $request->user());

        return redirect()
            ->route('pelanggan.servis.show', $servis)
            ->with('status', 'Foto diunggah.');
    }

    /** Setujui estimasi (fitur 27). */
    public function setujui(Request $request, Servis $servis, Estimasi $estimasi, EstimasiService $service): RedirectResponse
    {
        $this->authorize('putuskanEstimasi', $servis);
        abort_unless($estimasi->id_servis === $servis->id_servis, 404);

        $service->putuskan($estimasi, true, $request->user(), $request->string('catatan')->trim()->value() ?: null);

        return redirect()
            ->route('pelanggan.servis.show', $servis)
            ->with('status', 'Estimasi disetujui.');
    }

    /** Tolak estimasi (fitur 27). */
    public function tolak(Request $request, Servis $servis, Estimasi $estimasi, EstimasiService $service): RedirectResponse
    {
        $this->authorize('putuskanEstimasi', $servis);
        abort_unless($estimasi->id_servis === $servis->id_servis, 404);

        $service->putuskan($estimasi, false, $request->user(), $request->string('catatan')->trim()->value() ?: null);

        return redirect()
            ->route('pelanggan.servis.show', $servis)
            ->with('status', 'Estimasi ditolak.');
    }
}
