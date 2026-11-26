<?php

use App\Http\Controllers\Auth\AktivasiAkunController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Middleware\AkunAktif;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('lupa-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('lupa-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password/{token}', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Aktivasi akun undangan (fitur 6): token di path, berlaku sekali pakai 72 jam.
    Route::get('aktivasi/{token}', [AktivasiAkunController::class, 'create'])
        ->name('aktivasi.create');

    Route::post('aktivasi/{token}', [AktivasiAkunController::class, 'store'])
        ->name('aktivasi.store');
});

Route::middleware('auth')->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->withoutMiddleware(AkunAktif::class)
        ->name('logout');
});
