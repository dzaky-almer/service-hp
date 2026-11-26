<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\UndanganService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AktivasiAkunController extends Controller
{
    /** Pesan tunggal untuk token tidak dikenal, sudah dipakai, maupun kedaluwarsa. */
    public const PESAN_TOKEN_TIDAK_VALID = 'Tautan aktivasi tidak valid atau sudah kedaluwarsa.';

    public function __construct(private UndanganService $undangan) {}

    /**
     * Tampilkan form penetapan password untuk token undangan (fitur 6).
     */
    public function create(string $token): View|RedirectResponse
    {
        $undangan = $this->undangan->cariUndanganValid($token);

        if ($undangan === null) {
            return redirect()->route('login')
                ->withErrors(['email' => self::PESAN_TOKEN_TIDAK_VALID]);
        }

        return view('auth.aktivasi', [
            'token' => $token,
            'pengguna' => $undangan->pengguna,
        ]);
    }

    /**
     * Aktifkan akun: password ditetapkan pengguna sendiri, token terpakai sekali.
     */
    public function store(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal :min karakter.',
        ]);

        $pengguna = $this->undangan->aktifkanAkun($token, $data['password']);

        if ($pengguna === null) {
            return redirect()->route('login')
                ->withErrors(['email' => self::PESAN_TOKEN_TIDAK_VALID]);
        }

        Auth::login($pengguna);
        $request->session()->regenerate();

        return redirect(route('dashboard', absolute: false))
            ->with('status', 'Akun berhasil diaktifkan. Selamat datang, '.$pengguna->nama.'.');
    }
}
