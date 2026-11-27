<?php

use App\Models\Pembayaran;
use App\Services\PembayaranService;

it('command menandai pembayaran gateway menggantung sebagai gagal', function () {
    $uji = tahap6SiapDiambil();
    $pembayaran = app(PembayaranService::class);

    $lama = $pembayaran->buat($uji['servis'], 10000, 'gateway', $uji['admin']);
    $lama->forceFill(['dibuat_pada' => now()->subHours(PembayaranService::MASA_KEDALUWARSA_JAM + 1)])->save();

    $baru = $pembayaran->buat($uji['servis'], 5000, 'gateway', $uji['admin']);
    $tunai = $pembayaran->buat($uji['servis'], 5000, 'tunai', $uji['admin']);
    $tunai->forceFill(['dibuat_pada' => now()->subDays(3)])->save();

    $this->artisan('pembayaran:kedaluwarsa')->assertExitCode(0);

    expect($lama->refresh()->status)->toBe(Pembayaran::GAGAL)
        ->and($lama->alasan)->toContain('Kedaluwarsa')
        ->and($baru->refresh()->status)->toBe(Pembayaran::MENUNGGU)
        ->and($tunai->refresh()->status)->toBe(Pembayaran::MENUNGGU);
});
