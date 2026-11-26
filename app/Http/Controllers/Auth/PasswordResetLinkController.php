<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Pesan netral: sama untuk email terdaftar maupun tidak terdaftar
     * (SECURITY.md: keberadaan akun tidak boleh bocor lewat respons).
     */
    private const PESAN_NETRAL = 'Jika email tersebut terdaftar, kami sudah mengirim tautan reset password ke email itu.';

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Hasil apa pun (email tidak ada, token masih muda, tautan terkirim)
        // dijawab sama supaya `RESET_THROTTLED` tidak bisa dipakai menebak email.
        Password::sendResetLink([
            'email' => $request->string('email')->lower()->value(),
        ]);

        return back()->with('status', self::PESAN_NETRAL);
    }
}
