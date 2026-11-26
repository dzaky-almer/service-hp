<?php

use App\Http\Controllers\Admin\StafController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profil.destroy');

    Route::put('/profil/password', [PasswordController::class, 'update'])->name('profil.password');
});

// Area Owner: staf, undangan, dan konfigurasi bisnis (SECURITY.md §2).
Route::middleware(['auth', 'peran:owner'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('staf', [StafController::class, 'index'])->name('staf.index');
    Route::post('staf', [StafController::class, 'store'])->name('staf.store');
    Route::post('staf/{pengguna}/status', [StafController::class, 'status'])->name('staf.status');
});

require __DIR__.'/auth.php';
