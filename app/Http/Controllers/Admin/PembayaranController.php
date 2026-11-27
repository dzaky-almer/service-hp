<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PembalikanRequest;
use App\Http\Requests\Admin\PembayaranRequest;
use App\Models\Pembayaran;
use App\Models\Servis;
use App\Services\PembayaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Pembayaran: catat (40), konfirmasi/tolak (41), pembalikan (43). */
class PembayaranController extends Controller
{
    public function __construct(private readonly PembayaranService $pembayaran) {}

    public function store(PembayaranRequest $request, Servis $servis): RedirectResponse
    {
        $this->authorize('kelola', $servis);

        $this->pembayaran->buat(
            $servis,
            (float) $request->validated('nominal'),
            $request->validated('metode'),
            $request->user(),
        );

        return redirect()
            ->route('admin.servis.show', $servis)
            ->with('status', 'Pembayaran dicatat, menunggu konfirmasi.');
    }

    public function konfirmasi(Request $request, Pembayaran $pembayaran): RedirectResponse
    {
        $this->authorize('kelola', $pembayaran->servis);

        $this->pembayaran->konfirmasi($pembayaran, $request->user());

        return redirect()
            ->route('admin.servis.show', $pembayaran->id_servis)
            ->with('status', 'Pembayaran dikonfirmasi berhasil.');
    }

    public function tolak(Request $request, Pembayaran $pembayaran): RedirectResponse
    {
        $this->authorize('kelola', $pembayaran->servis);

        $alasan = $request->validate(['alasan' => ['nullable', 'string', 'max:255']])['alasan'] ?? null;

        $this->pembayaran->tolak($pembayaran, $request->user(), $alasan);

        return redirect()
            ->route('admin.servis.show', $pembayaran->id_servis)
            ->with('status', 'Pembayaran ditolak.');
    }

    public function pembalikan(PembalikanRequest $request, Pembayaran $pembayaran): RedirectResponse
    {
        $this->authorize('kelola', $pembayaran->servis);

        $this->pembayaran->pembalikan(
            $pembayaran,
            (float) $request->validated('nominal'),
            $request->validated('alasan'),
            $request->user(),
        );

        return redirect()
            ->route('admin.servis.show', $pembayaran->id_servis)
            ->with('status', 'Pembayaran dibalik dengan baris baru.');
    }
}
