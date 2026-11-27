<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Riwayat perubahan (fitur 53). Baca saja; `audit_log` hanya INSERT. */
class AuditController extends Controller
{
    public function index(Request $request)
    {
        $cari = $request->string('cari')->trim()->value();
        $aksi = $request->string('aksi')->trim()->value();

        $daftar = DB::table('audit_log')
            ->leftJoin('pengguna', 'pengguna.id_pengguna', '=', 'audit_log.id_pengguna')
            ->when($cari !== '', fn ($q) => $q->where(fn ($x) => $x
                ->where('pengguna.nama', 'like', "%{$cari}%")
                ->orWhere('audit_log.objek_tipe', 'like', "%{$cari}%")
                ->orWhere('audit_log.aksi', 'like', "%{$cari}%")
                ->orWhere('audit_log.objek_id', 'like', "%{$cari}%")))
            ->when($aksi !== '', fn ($q) => $q->where('audit_log.aksi', $aksi))
            ->select('audit_log.*', 'pengguna.nama as pelaku')
            ->orderByDesc('audit_log.id_audit')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit.index', [
            'daftar' => $daftar,
            'cari' => $cari,
            'aksi' => $aksi,
            'daftarAksi' => DB::table('audit_log')->distinct()->orderBy('aksi')->pluck('aksi'),
        ]);
    }
}
