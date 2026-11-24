<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Pengguna;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'no_hp' => [
                'required', 'string', 'max:20',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (Pelanggan::where('no_hp', $value)->whereNull('id_pengguna')->exists()) {
                        $fail('No. HP ini milik pelanggan toko yang belum punya akun. Hubungi toko untuk aktivasi.');

                        return;
                    }

                    if (Pengguna::where('no_hp', $value)->exists() || Pelanggan::where('no_hp', $value)->exists()) {
                        $fail('No. HP sudah terdaftar.');
                    }
                },
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:'.Pengguna::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $pengguna = DB::transaction(function () use ($request) {
            $pengguna = new Pengguna($request->only('nama', 'no_hp', 'email', 'password'));
            $pengguna->peran = Pengguna::PERAN_CUSTOMER;
            $pengguna->status_akun = Pengguna::STATUS_AKTIF;
            $pengguna->save();

            Pelanggan::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'nama' => $pengguna->nama,
                'no_hp' => $pengguna->no_hp,
                'email' => $pengguna->email,
            ]);

            return $pengguna;
        });

        event(new Registered($pengguna));

        Auth::login($pengguna);

        return redirect(route('dashboard', absolute: false));
    }
}
