<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PelangganRequest;
use App\Models\Pelanggan;
use App\Notifications\UndanganAktivasi;
use App\Services\PelangganService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class PelangganController extends Controller
{
    /**
     * Daftar dan pencarian pelanggan (fitur 12). Data master tidak dihapus
     * (BR-17), jadi pelanggan nonaktif tetap tampil dengan statusnya.
     */
    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));

        $pelanggan = Pelanggan::query()
            ->with('pengguna')
            ->when($cari !== '', fn ($q) => $q->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                    ->orWhere('no_hp', 'like', "%{$cari}%")
                    ->orWhere('email', 'like', "%{$cari}%");
            }))
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pelanggan.index', ['pelanggan' => $pelanggan, 'cari' => $cari]);
    }

    /**
     * Buat pelanggan (fitur 11), opsional sekaligus mengirim undangan akun.
     */
    public function store(PelangganRequest $request, PelangganService $service): RedirectResponse
    {
        $data = $request->validated();
        $pelanggan = $service->simpan(null, $data, $request->user());

        $pesan = 'Pelanggan '.$pelanggan->nama.' ditambahkan.';

        if ($request->boolean('kirim_undangan')) {
            $pesan .= ' '.$this->kirimUndangan($pelanggan, $data['email'] ?? null, $service, $request);
        }

        return Redirect::route('admin.pelanggan.index')->with('status', $pesan);
    }

    /**
     * Ubah data pelanggan (fitur 13). Undangan akun hanya untuk pelanggan yang
     * belum punya akun — akun yang sudah ada dikelola lewat status akunnya.
     */
    public function update(PelangganRequest $request, Pelanggan $pelanggan, PelangganService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->simpan($pelanggan, $data, $request->user());

        $pesan = 'Data pelanggan '.$pelanggan->nama.' diperbarui.';

        if ($request->boolean('kirim_undangan') && $pelanggan->id_pengguna === null) {
            $pesan .= ' '.$this->kirimUndangan($pelanggan, $data['email'] ?? null, $service, $request);
        }

        return Redirect::route('admin.pelanggan.index')->with('status', $pesan);
    }

    /**
     * Nonaktifkan pelanggan (fitur 14). Tidak ada jalur hapus permanen (BR-17).
     */
    public function nonaktifkan(Request $request, Pelanggan $pelanggan, PelangganService $service): RedirectResponse
    {
        $punyaAkun = $pelanggan->id_pengguna !== null;

        $service->nonaktifkan($pelanggan, $request->user());

        return Redirect::route('admin.pelanggan.index')->with('status',
            'Pelanggan '.$pelanggan->nama.' dinonaktifkan.'
            .($punyaAkun ? ' Akun yang tertaut ikut dinonaktifkan.' : ''));
    }

    /**
     * Kirim undangan akun untuk pelanggan tanpa akun, lalu kirim emailnya setelah
     * transaksi selesai supaya undangan yang batal tidak mengirim apa pun.
     */
    private function kirimUndangan(Pelanggan $pelanggan, ?string $email, PelangganService $service, Request $request): string
    {
        if ($email === null || $email === '') {
            return 'Undangan akun tidak dikirim: email pelanggan belum diisi.';
        }

        $hasil = $service->kirimUndanganAkun($pelanggan, $email, $request->user());

        $hasil['pengguna']->notify(new UndanganAktivasi($hasil['pengguna'], $hasil['token']));

        return 'Undangan akun dikirim ke '.$email.'.';
    }
}
