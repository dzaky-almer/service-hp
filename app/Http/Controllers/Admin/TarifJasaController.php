<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TarifJasaRequest;
use App\Models\TarifJasa;
use App\Services\TarifJasaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TarifJasaController extends Controller
{
    public function __construct(private TarifJasaService $tarif) {}

    /**
     * Fitur 52: daftar tarif jasa (khusus Owner).
     */
    public function index(): View
    {
        return view('admin.tarif-jasa.index', [
            'daftar' => TarifJasa::query()->orderBy('nama')->get(),
        ]);
    }

    public function store(TarifJasaRequest $request): RedirectResponse
    {
        $this->tarif->simpan(null, $request->validated(), $request->user());

        return redirect()->route('admin.tarif-jasa.index')->with('status', 'Tarif jasa disimpan.');
    }

    public function update(TarifJasaRequest $request, TarifJasa $tarifJasa): RedirectResponse
    {
        $this->tarif->simpan($tarifJasa, $request->validated(), $request->user());

        return redirect()->route('admin.tarif-jasa.index')->with('status', 'Tarif jasa diperbarui.');
    }

    public function destroy(TarifJasa $tarifJasa): RedirectResponse
    {
        $this->tarif->hapus($tarifJasa, request()->user());

        return redirect()->route('admin.tarif-jasa.index')->with('status', 'Tarif jasa dihapus.');
    }
}
