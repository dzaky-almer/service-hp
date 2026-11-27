<?php

use App\Models\Pembayaran;
use App\Models\WebhookLog;
use App\Services\PembayaranService;

function kirimWebhook(array $data, ?string $tandaTangan = null)
{
    $raw = json_encode($data, JSON_UNESCAPED_SLASHES);
    $tandaTangan ??= hash_hmac('sha256', $raw, (string) config('services.gateway.secret'));

    return test()->call('POST', '/webhook/pembayaran', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_SIGNATURE' => $tandaTangan,
    ], $raw);
}

it('menolak webhook dengan tanda tangan salah, payload tetap dicatat', function () {
    $uji = tahap6SiapDiambil();
    $bayar = app(PembayaranService::class)
        ->buat($uji['servis'], 15000, 'gateway', $uji['admin']);

    $respons = kirimWebhook([
        'id_transaksi_eksternal' => $bayar->id_transaksi_eksternal,
        'status' => 'berhasil',
        'nominal' => 15000,
    ], 'tanda-tangan-palsu');

    $respons->assertStatus(401);

    expect($bayar->refresh()->status)->toBe(Pembayaran::MENUNGGU)
        ->and(WebhookLog::count())->toBe(1)
        ->and(WebhookLog::sole()->tanda_tangan_valid)->toBeFalse();
});

it('webhook bertanda tangan valid menandai pembayaran berhasil', function () {
    $uji = tahap6SiapDiambil();
    $bayar = app(PembayaranService::class)
        ->buat($uji['servis'], 25000, 'gateway', $uji['admin']);

    kirimWebhook([
        'id_transaksi_eksternal' => $bayar->id_transaksi_eksternal,
        'status' => 'berhasil',
        'nominal' => 25000,
    ])->assertOk();

    expect($bayar->refresh()->status)->toBe(Pembayaran::BERHASIL)
        ->and($bayar->diputuskan_pada)->not->toBeNull()
        ->and($uji['servis']->totalDibayar())->toBe(25000.0)
        ->and(WebhookLog::sole()->tanda_tangan_valid)->toBeTrue();
});

it('webhook ganda bersifat idempoten', function () {
    $uji = tahap6SiapDiambil();
    $bayar = app(PembayaranService::class)
        ->buat($uji['servis'], 10000, 'gateway', $uji['admin']);

    $isi = [
        'id_transaksi_eksternal' => $bayar->id_transaksi_eksternal,
        'status' => 'berhasil',
        'nominal' => 10000,
    ];

    kirimWebhook($isi)->assertOk();
    kirimWebhook($isi)->assertOk();

    expect($uji['servis']->refresh()->totalDibayar())->toBe(10000.0)
        ->and(WebhookLog::count())->toBe(2);
});

it('webhook dengan nominal berbeda tidak mengubah status', function () {
    $uji = tahap6SiapDiambil();
    $bayar = app(PembayaranService::class)
        ->buat($uji['servis'], 30000, 'gateway', $uji['admin']);

    kirimWebhook([
        'id_transaksi_eksternal' => $bayar->id_transaksi_eksternal,
        'status' => 'berhasil',
        'nominal' => 1000,
    ])->assertOk();

    expect($bayar->refresh()->status)->toBe(Pembayaran::MENUNGGU);
});

it('webhook untuk transaksi tak dikenal hanya dicatat', function () {
    kirimWebhook([
        'id_transaksi_eksternal' => 'SIM-TIDAK-ADA',
        'status' => 'berhasil',
        'nominal' => 1000,
    ])->assertOk();

    expect(WebhookLog::count())->toBe(1)
        ->and(Pembayaran::count())->toBe(0);
});

it('webhook gagal menandai pembayaran gagal dengan alasan', function () {
    $uji = tahap6SiapDiambil();
    $bayar = app(PembayaranService::class)
        ->buat($uji['servis'], 5000, 'gateway', $uji['admin']);

    kirimWebhook([
        'id_transaksi_eksternal' => $bayar->id_transaksi_eksternal,
        'status' => 'gagal',
        'nominal' => 5000,
    ])->assertOk();

    expect($bayar->refresh()->status)->toBe(Pembayaran::GAGAL)
        ->and($bayar->alasan)->toBe('Ditolak gateway')
        ->and($uji['servis']->refresh()->totalDibayar())->toBe(0.0);
});
