<?php

use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Services\PembayaranService;

it('admin mencatat pembayaran tunai lalu konfirmasi melunasi tagihan', function () {
    $uji = tahap6SiapDiambil();
    $tagihan = $uji['servis']->totalTagihan();

    $this->actingAs($uji['admin'])
        ->post(route('admin.servis.pembayaran', $uji['servis']), [
            'metode' => 'tunai',
            'nominal' => $tagihan,
        ])
        ->assertRedirect(route('admin.servis.show', $uji['servis']))
        ->assertSessionHasNoErrors();

    $bayar = $uji['servis']->pembayaran()->sole();

    expect($bayar->status)->toBe(Pembayaran::MENUNGGU)
        ->and($bayar->id_transaksi_eksternal)->toBeNull()
        ->and($uji['servis']->refresh()->lunas())->toBeFalse();

    $this->actingAs($uji['admin'])
        ->post(route('admin.pembayaran.konfirmasi', $bayar))
        ->assertSessionHasNoErrors();

    expect($bayar->refresh()->status)->toBe(Pembayaran::BERHASIL)
        ->and($uji['servis']->refresh()->totalDibayar())->toBe((float) $tagihan)
        ->and($uji['servis']->refresh()->lunas())->toBeTrue();
});

it('menolak nominal melebihi sisa tagihan', function () {
    $uji = tahap6SiapDiambil();

    $this->actingAs($uji['admin'])
        ->post(route('admin.servis.pembayaran', $uji['servis']), [
            'metode' => 'tunai',
            'nominal' => $uji['servis']->totalTagihan() + 1,
        ])
        ->assertSessionHasErrors('nominal');

    expect($uji['servis']->pembayaran()->count())->toBe(0);
});

it('pembayaran yang sudah diputuskan tidak bisa dikonfirmasi ulang', function () {
    $uji = tahap6SiapDiambil();
    $bayar = app(PembayaranService::class)
        ->buat($uji['servis'], 10000, 'tunai', $uji['admin']);

    $this->actingAs($uji['admin'])->post(route('admin.pembayaran.konfirmasi', $bayar));
    $this->actingAs($uji['admin'])
        ->post(route('admin.pembayaran.konfirmasi', $bayar))
        ->assertSessionHasErrors('status');
});

it('menolak pembayaran pada servis yang dibatalkan', function () {
    $uji = tahap6SiapDiambil();
    $uji['servis']->forceFill(['status' => Servis::DIBATALKAN])->save();

    $this->actingAs($uji['admin'])
        ->post(route('admin.servis.pembayaran', $uji['servis']), ['metode' => 'tunai', 'nominal' => 5000])
        ->assertSessionHasErrors('nominal');
});

it('pelanggan membuat pembayaran gateway miliknya, nominal divalidasi', function () {
    $uji = tahap6SiapDiambil();

    $this->actingAs($uji['customer'])
        ->post(route('pelanggan.servis.bayar', $uji['servis']), ['nominal' => 20000])
        ->assertSessionHasNoErrors();

    $bayar = $uji['servis']->pembayaran()->sole();

    expect($bayar->metode)->toBe('gateway')
        ->and($bayar->status)->toBe(Pembayaran::MENUNGGU)
        ->and($bayar->id_transaksi_eksternal)->toStartWith('SIM-');

    $this->actingAs($uji['customer'])
        ->post(route('pelanggan.servis.bayar', $uji['servis']), ['nominal' => 0])
        ->assertSessionHasErrors('nominal');
});

it('pelanggan lain tidak bisa membayar servis bukan miliknya', function () {
    $uji = tahap6SiapDiambil();
    $lain = akunPeranUji(Pengguna::PERAN_CUSTOMER);

    $this->actingAs($lain)
        ->post(route('pelanggan.servis.bayar', $uji['servis']), ['nominal' => 10000])
        ->assertForbidden();
});

it('teknisi tidak boleh mencatat pembayaran', function () {
    $uji = tahap6SiapDiambil();

    $this->actingAs($uji['teknisi'])
        ->post(route('admin.servis.pembayaran', $uji['servis']), ['metode' => 'tunai', 'nominal' => 1000])
        ->assertForbidden();
});
