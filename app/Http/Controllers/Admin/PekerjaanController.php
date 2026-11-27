<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Servis;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Fitur 29: daftar semua pekerjaan beserta teknisinya (admin/owner). */
class PekerjaanController extends Controller
{
    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));

        $daftar = Servis::query()
            ->with(['pelanggan', 'teknisi'])
            ->whereNotIn('status', [Servis::DIBATALKAN])
            ->when($cari !== '', function ($query) use ($cari): void {
                $query->where(function ($query) use ($cari): void {
                    $query->where('nomor_servis', 'like', "%{$cari}%")
                        ->orWhere('merek', 'like', "%{$cari}%")
                        ->orWhere('model', 'like', "%{$cari}%")
                        ->orWhereHas('pelanggan', fn ($p) => $p->where('nama', 'like', "%{$cari}%"));
                });
            })
            ->orderByRaw("case when status in ('siap_diambil','selesai','ditolak') then 1 else 0 end")
            ->orderByDesc('id_servis')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pekerjaan.index', compact('daftar', 'cari'));
    }
}
