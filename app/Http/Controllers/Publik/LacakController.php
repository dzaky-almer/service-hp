<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\Servis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pelacakan status tanpa login (fitur 10). Hanya merek, model, status, dan tanggal
 * masuk yang ditampilkan (BR-14) — tanpa data pelanggan/keluhan/nota.
 */
class LacakController extends Controller
{
    public function index(): View
    {
        return view('publik.lacak');
    }

    public function cari(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode_tracking' => ['required', 'string', 'min:6', 'max:20'],
        ], [
            'kode_tracking.required' => 'Kode tracking wajib diisi.',
            'kode_tracking.min' => 'Kode tracking tidak valid.',
        ]);

        $kode = strtoupper(trim($data['kode_tracking']));

        if (! Servis::where('kode_tracking', $kode)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['kode_tracking' => 'Kode tracking tidak ditemukan. Periksa kembali tanda terima Anda.']);
        }

        return redirect()->route('publik.lacak.hasil', $kode);
    }

    public function hasil(string $kode): View
    {
        $servis = Servis::where('kode_tracking', strtoupper($kode))
            ->with('riwayat')
            ->firstOrFail();

        return view('publik.lacak-hasil', compact('servis'));
    }
}
