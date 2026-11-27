<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PengaturanRequest;
use App\Models\PengaturanBisnis;
use App\Services\PengaturanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function __construct(private PengaturanService $pengaturan) {}

    /**
     * Fitur 52: konfigurasi bisnis (khusus Owner, SECURITY §2).
     */
    public function edit(): View
    {
        return view('admin.pengaturan.edit', ['nilai' => PengaturanBisnis::semua()]);
    }

    public function update(PengaturanRequest $request): RedirectResponse
    {
        $this->pengaturan->simpan($request->validated(), $request->user());

        return redirect()->route('admin.pengaturan.edit')->with('status', 'Pengaturan bisnis disimpan.');
    }
}
