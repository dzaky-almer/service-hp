<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UndangStafRequest;
use App\Models\Pengguna;
use App\Models\UndanganAkun;
use App\Notifications\UndanganAktivasi;
use App\Services\StafService;
use App\Services\UndanganService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class StafController extends Controller
{
    /**
     * Daftar staf (Admin/Technician/Owner) dan status undangannya. Fitur 5, 7.
     */
    public function index(): View
    {
        $staf = Pengguna::whereIn('peran', [
            Pengguna::PERAN_OWNER,
            Pengguna::PERAN_ADMIN,
            Pengguna::PERAN_TEKNISI,
        ])->orderBy('nama')->get();

        $undangan = UndanganAkun::whereIn('id_pengguna', $staf->pluck('id_pengguna'))
            ->orderByDesc('id_undangan')
            ->get()
            ->keyBy('id_pengguna');

        return view('admin.staf.index', ['staf' => $staf, 'undangan' => $undangan]);
    }

    /**
     * Undang staf baru sebagai Admin/Technician (fitur 5).
     */
    public function store(UndangStafRequest $request, UndanganService $undanganService): RedirectResponse
    {
        $data = $request->validated();

        $hasil = $undanganService->undangStaf(
            $data['nama'],
            $data['email'],
            $data['no_hp'] ?? null,
            $data['peran'],
            $request->user(),
        );

        // Dikirim setelah transaksi selesai supaya undangan yang batal tidak mengirim email.
        $hasil['pengguna']->notify(new UndanganAktivasi($hasil['pengguna'], $hasil['token']));

        return Redirect::route('admin.staf.index')
            ->with('status', 'Undangan dikirim ke '.$data['email'].'. Tautan berlaku '
                .UndanganAkun::MASA_BERLAKU_JAM.' jam.');
    }

    /**
     * Aktifkan/nonaktifkan akun staf (fitur 7). Area ini hanya Owner
     * (SECURITY.md §2), dan akun Owner tidak dapat dinonaktifkan — termasuk
     * akun sendiri — sesuai BR-12. Akun berstatus Menunggu Aktivasi hanya bisa
     * menjadi aktif lewat token undangan (fitur 6), bukan lewat halaman ini.
     */
    public function status(Request $request, Pengguna $pengguna, StafService $stafService): RedirectResponse
    {
        $data = $request->validate(
            ['status_akun' => ['required', 'in:aktif,nonaktif']],
            ['status_akun.in' => 'Status akun hanya boleh aktif atau nonaktif.'],
        );

        abort_if($pengguna->peran === Pengguna::PERAN_OWNER, 403);
        abort_if($pengguna->is($request->user()), 403);
        abort_if($pengguna->status_akun === Pengguna::STATUS_MENUNGGU_AKTIVASI, 403);

        $stafService->ubahStatus($pengguna, $data['status_akun'], $request->user());

        return Redirect::route('admin.staf.index')
            ->with('status', $pengguna->nama.' kini berstatus '
                .Pengguna::LABEL_STATUS_AKUN[$data['status_akun']].'.');
    }
}
