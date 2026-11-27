<?php

use App\Http\Controllers\Admin\PekerjaanController as PekerjaanAdminController;
use App\Http\Controllers\Admin\PelangganController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\ServisController;
use App\Http\Controllers\Admin\SparepartController;
use App\Http\Controllers\Admin\StafController;
use App\Http\Controllers\Admin\TarifJasaController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\FotoServisController;
use App\Http\Controllers\Pelanggan\ServisController as ServisPelangganController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Publik\BerandaController;
use App\Http\Controllers\Publik\LacakController;
use App\Http\Controllers\Teknisi\PekerjaanController;
use Illuminate\Support\Facades\Route;

// Halaman publik (fitur 9, 10): info bisnis dan pelacakan status tanpa login.
Route::get('/', BerandaController::class)->name('publik.beranda');
Route::get('/lacak', [LacakController::class, 'index'])->name('publik.lacak.index');
Route::post('/lacak', [LacakController::class, 'cari'])->middleware('throttle:10,1')->name('publik.lacak.cari');
Route::get('/lacak/{kode}', [LacakController::class, 'hasil'])
    ->where('kode', '[A-Za-z0-9]{6,20}')
    ->middleware('throttle:30,1')
    ->name('publik.lacak.hasil');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profil.destroy');

    Route::put('/profil/password', [PasswordController::class, 'update'])->name('profil.password');

    // Foto servis di disk privat (SECURITY.md §4); izin diperiksa ServisPolicy.
    Route::get('/foto/{foto}', [FotoServisController::class, 'show'])->name('foto.show');
});

// Area Admin dan Owner: master data pelanggan (fitur 11–14).
Route::middleware(['auth', 'peran:admin,owner'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('pelanggan', [PelangganController::class, 'index'])->name('pelanggan.index');
    Route::post('pelanggan', [PelangganController::class, 'store'])->name('pelanggan.store');
    Route::put('pelanggan/{pelanggan}', [PelangganController::class, 'update'])->name('pelanggan.update');
    Route::post('pelanggan/{pelanggan}/nonaktif', [PelangganController::class, 'nonaktifkan'])->name('pelanggan.nonaktif');

    // Spare part dan stok (fitur 35, 36). Tanpa halaman create/edit: form ada di daftar & detail.
    Route::resource('sparepart', SparepartController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('sparepart/{sparepart}/penyesuaian', [SparepartController::class, 'penyesuaian'])->name('sparepart.penyesuaian');

    // Daftar semua pekerjaan lintas teknisi (fitur 29).
    Route::get('pekerjaan', [PekerjaanAdminController::class, 'index'])->name('pekerjaan.index');

    // Service order (fitur 15–22). Detail servis memuat aksi foto/teknisi/batal/tanda terima.
    Route::resource('servis', ServisController::class)
        ->only(['index', 'create', 'store', 'show', 'update'])
        ->parameters(['servis' => 'servis']);
    Route::post('servis/{servis}/foto', [ServisController::class, 'foto'])->name('servis.foto');
    Route::post('servis/{servis}/teknisi', [ServisController::class, 'teknisi'])->name('servis.teknisi');
    Route::post('servis/{servis}/batal', [ServisController::class, 'batal'])->name('servis.batal');
    Route::get('servis/{servis}/tanda-terima', [ServisController::class, 'tandaTerima'])->name('servis.tanda-terima');

    // Diagnosis, estimasi, persetujuan (fitur 23–28).
    Route::post('servis/{servis}/diagnosis', [ServisController::class, 'diagnosis'])->name('servis.diagnosis');
    Route::post('servis/{servis}/mulai-diagnosis', [ServisController::class, 'mulaiDiagnosis'])->name('servis.diagnosis.mulai');
    Route::post('servis/{servis}/estimasi', [ServisController::class, 'simpanEstimasi'])->name('servis.estimasi.simpan');
    Route::post('servis/{servis}/estimasi/revisi', [ServisController::class, 'revisiEstimasi'])->name('servis.estimasi.revisi');
    Route::post('servis/{servis}/estimasi/{estimasi}/kirim', [ServisController::class, 'kirimEstimasi'])->name('servis.estimasi.kirim');
    Route::post('servis/{servis}/kembalikan', [ServisController::class, 'kembalikan'])->name('servis.kembalikan');

    // Pengerjaan, catatan, spare part, QC, siap diambil (fitur 30–34, 37).
    Route::post('servis/{servis}/status', [ServisController::class, 'status'])->name('servis.status');
    Route::post('servis/{servis}/catatan', [ServisController::class, 'catatan'])->name('servis.catatan');
    Route::post('servis/{servis}/sparepart', [ServisController::class, 'pakaiSparepart'])->name('servis.pakai-sparepart');
    Route::post('servis/{servis}/qc', [ServisController::class, 'qc'])->name('servis.qc');
    Route::post('servis/{servis}/siap-diambil', [ServisController::class, 'siapDiambil'])->name('servis.siap-diambil');
});

// Area Owner: staf, undangan, dan konfigurasi bisnis (SECURITY.md §2).
Route::middleware(['auth', 'peran:owner'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('staf', [StafController::class, 'index'])->name('staf.index');
    Route::post('staf', [StafController::class, 'store'])->name('staf.store');
    Route::post('staf/{pengguna}/status', [StafController::class, 'status'])->name('staf.status');

    // Konfigurasi bisnis + tarif jasa (fitur 52, khusus Owner).
    Route::get('pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
    Route::put('pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
    Route::resource('tarif-jasa', TarifJasaController::class)->only(['index', 'store', 'update', 'destroy']);
});

// Area pelanggan: servis miliknya sendiri (fitur 28–30).
Route::middleware(['auth', 'peran:customer'])->prefix('pelanggan')->name('pelanggan.')->group(function () {
    Route::get('servis', [ServisPelangganController::class, 'index'])->name('servis.index');
    Route::get('servis/{servis}', [ServisPelangganController::class, 'show'])->name('servis.show');
    Route::post('servis/{servis}/foto', [ServisPelangganController::class, 'foto'])->name('servis.foto');

    // Persetujuan estimasi (fitur 27): customer pemilik servis saja.
    Route::post('servis/{servis}/estimasi/{estimasi}/setujui', [ServisPelangganController::class, 'setujui'])->name('servis.estimasi.setujui');
    Route::post('servis/{servis}/estimasi/{estimasi}/tolak', [ServisPelangganController::class, 'tolak'])->name('servis.estimasi.tolak');
});

// Area teknisi: pekerjaan yang ditugaskan (fitur 23–27).
Route::middleware(['auth', 'peran:teknisi'])->prefix('teknisi')->name('teknisi.')->group(function () {
    Route::get('pekerjaan', [PekerjaanController::class, 'index'])->name('pekerjaan.index');
    Route::get('pekerjaan/{servis}', [PekerjaanController::class, 'show'])->name('pekerjaan.show');
    Route::put('pekerjaan/{servis}', [PekerjaanController::class, 'update'])->name('pekerjaan.update');
    Route::post('pekerjaan/{servis}/foto', [PekerjaanController::class, 'foto'])->name('pekerjaan.foto');

    // Diagnosis & draf estimasi oleh teknisi (fitur 23, 24, 28).
    Route::post('pekerjaan/{servis}/diagnosis', [PekerjaanController::class, 'diagnosis'])->name('pekerjaan.diagnosis');
    Route::post('pekerjaan/{servis}/mulai-diagnosis', [PekerjaanController::class, 'mulaiDiagnosis'])->name('pekerjaan.diagnosis.mulai');
    Route::post('pekerjaan/{servis}/estimasi', [PekerjaanController::class, 'simpanEstimasi'])->name('pekerjaan.estimasi.simpan');
    Route::post('pekerjaan/{servis}/estimasi/revisi', [PekerjaanController::class, 'revisiEstimasi'])->name('pekerjaan.estimasi.revisi');

    // Pengerjaan, catatan, spare part, QC, siap diambil (fitur 30–34, 37).
    Route::post('pekerjaan/{servis}/status', [PekerjaanController::class, 'status'])->name('pekerjaan.status');
    Route::post('pekerjaan/{servis}/catatan', [PekerjaanController::class, 'catatan'])->name('pekerjaan.catatan');
    Route::post('pekerjaan/{servis}/sparepart', [PekerjaanController::class, 'pakaiSparepart'])->name('pekerjaan.pakai-sparepart');
    Route::post('pekerjaan/{servis}/qc', [PekerjaanController::class, 'qc'])->name('pekerjaan.qc');
    Route::post('pekerjaan/{servis}/siap-diambil', [PekerjaanController::class, 'siapDiambil'])->name('pekerjaan.siap-diambil');
});

require __DIR__.'/auth.php';
