<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Servis;
use Illuminate\Http\Request;

/** Dashboard customer (fitur 46): servis miliknya sendiri. */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $daftar = Servis::query()
            ->whereHas('pelanggan', fn ($q) => $q->where('id_pengguna', $request->user()->id_pengguna))
            ->with('pelanggan')
            ->orderByDesc('id_servis')
            ->get();

        return view('pelanggan.dashboard', [
            'daftar' => $daftar,
            'aktif' => $daftar->filter(fn (Servis $s) => ! in_array($s->status, [Servis::SELESAI, Servis::DIBATALKAN], true))->count(),
            'sisaTagihan' => $daftar->sum(fn (Servis $s) => $s->sisaTagihan()),
        ]);
    }
}
