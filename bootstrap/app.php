<?php

use App\Http\Middleware\AkunAktif;
use App\Http\Middleware\PeranMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'peran' => PeranMiddleware::class,
            'akun.aktif' => AkunAktif::class,
        ]);

        // Akun non-aktif ditolak di semua route web. Kecuali logout, agar sesi
        // pengguna yang dinonaktifkan di tengah jalan tetap bisa ditutup.
        $middleware->web(append: [AkunAktif::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
