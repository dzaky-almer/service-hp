<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PenyesuaianStokRequest;
use App\Http\Requests\Admin\SparepartRequest;
use App\Models\Sparepart;
use App\Services\SparepartService;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SparepartController extends Controller
{
    public function __construct(
        private SparepartService $sparepart,
        private StokService $stok,
    ) {}

    /**
     * Fitur 35, 36: daftar spare part beserta stok turunan.
     */
    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari'));

        $daftar = Sparepart::query()
            ->when($cari !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('nama', 'like', "%{$cari}%")->orWhere('sku', 'like', "%{$cari}%"),
            ))
            ->withSum('mutasi as stok', 'jumlah')
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return view('admin.sparepart.index', compact('daftar', 'cari'));
    }

    public function show(Sparepart $sparepart): View
    {
        return view('admin.sparepart.show', [
            'sparepart' => $sparepart,
            'stok' => $this->stok->stok($sparepart),
            'mutasi' => $sparepart->mutasi()->with('dicatatOleh')->orderByDesc('waktu')->orderByDesc('id_mutasi')->get(),
        ]);
    }

    public function store(SparepartRequest $request): RedirectResponse
    {
        $this->sparepart->simpan(null, $request->validated(), $request->user());

        return redirect()->route('admin.sparepart.index')->with('status', 'Spare part disimpan.');
    }

    public function update(SparepartRequest $request, Sparepart $sparepart): RedirectResponse
    {
        $this->sparepart->simpan($sparepart, $request->validated(), $request->user());

        return redirect()->route('admin.sparepart.index')->with('status', 'Spare part diperbarui.');
    }

    public function destroy(Request $request, Sparepart $sparepart): RedirectResponse
    {
        $this->sparepart->hapus($sparepart, $request->user());

        return redirect()->route('admin.sparepart.index')->with('status', 'Spare part dihapus.');
    }

    /** Fitur 38: penyesuaian stok manual (alasan wajib, stok tidak boleh negatif). */
    public function penyesuaian(PenyesuaianStokRequest $request, Sparepart $sparepart): RedirectResponse
    {
        $this->stok->catatPenyesuaian(
            $sparepart,
            $request->validated('jumlah'),
            $request->validated('alasan'),
            $request->user(),
        );

        return redirect()
            ->route('admin.sparepart.show', $sparepart)
            ->with('status', 'Stok disesuaikan. Stok sekarang '.$this->stok->stok($sparepart).'.');
    }
}
