<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AkunAktif
{
    /**
     * Hanya akun berstatus `aktif` yang boleh memakai aplikasi.
     * Tamu dilewatkan; penolakan login ditangani alur auth.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $status = $request->user()?->status_akun;

        if ($status !== null && $status !== 'aktif') {
            abort(403);
        }

        return $next($request);
    }
}
