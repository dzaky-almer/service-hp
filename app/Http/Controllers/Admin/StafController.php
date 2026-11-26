<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UndangStafRequest;
use App\Models\Pengguna;
use App\Models\UndanganAkun;
use App\Notifications\UndanganAktivasi;
use App\Services\UndanganService;
use Illuminate\Http\RedirectResponse;
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
}
