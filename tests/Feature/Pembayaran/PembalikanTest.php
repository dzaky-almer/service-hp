<?php

use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Services\PembayaranService;
use App\Services\SerahTerimaService;
use Illuminate\Validation\ValidationException;

function bayarPertama(array $uji): Pembayaran
{
    return $uji['servis']->pembayaran()->where('jenis', Pembayaran::BAYAR)->sole();
}

it('owner membalik sebagian pembayaran dengan baris baru', function () {
    $uji = tahap6Lunas();
    $owner = akunPeranUji(Pengguna::PERAN_OWNER);
    $asal = bayarPertama($uji);
    $tagihan = $uji['servis']->totalTagihan();

    $this->actingAs($owner)
        ->post(route('admin.pembayaran.pembalikan', $asal), ['nominal' => 5000, 'alasan' => 'Kelebihan transfer'])
        ->assertSessionHasNoErrors();

    $uji['servis']->refresh();

    expect($uji['servis']->pembayaran()->count())->toBe(2)
        ->and($asal->refresh()->status)->toBe(Pembayaran::BERHASIL)
        ->and((float) $asal->refresh()->nominal)->toBe((float) $tagihan)
        ->and($uji['servis']->totalDibayar())->toBe($tagihan - 5000)
        ->and($uji['servis']->lunas())->toBeFalse();

    $pembalikan = $uji['servis']->pembayaran()->where('jenis', Pembayaran::PEMBALIKAN)->sole();

    expect($pembalikan->id_pembayaran_asal)->toBe($asal->id_pembayaran)
        ->and($pembalikan->alasan)->toBe('Kelebihan transfer')
        ->and($pembalikan->status)->toBe(Pembayaran::BERHASIL);
});

it('admin hanya boleh membalik pembayaran hari yang sama', function () {
    $uji = tahap6Lunas();
    $asal = bayarPertama($uji);
    $asal->forceFill(['dibuat_pada' => now()->subDay()])->save();

    $this->actingAs($uji['admin'])
        ->post(route('admin.pembayaran.pembalikan', $asal), ['nominal' => 1000, 'alasan' => 'Salah input'])
        ->assertSessionHasErrors('nominal');

    expect($uji['servis']->pembayaran()->count())->toBe(1);

    $owner = akunPeranUji(Pengguna::PERAN_OWNER);

    $this->actingAs($owner)
        ->post(route('admin.pembayaran.pembalikan', $asal), ['nominal' => 1000, 'alasan' => 'Salah input'])
        ->assertSessionHasNoErrors();

    expect($uji['servis']->pembayaran()->count())->toBe(2);
});

it('pembalikan melebihi nominal yang belum dibalik ditolak', function () {
    $uji = tahap6Lunas();
    $owner = akunPeranUji(Pengguna::PERAN_OWNER);
    $asal = bayarPertama($uji);

    $this->actingAs($owner)
        ->post(route('admin.pembayaran.pembalikan', $asal), [
            'nominal' => $uji['servis']->totalTagihan() + 1,
            'alasan' => 'Terlalu besar',
        ])
        ->assertSessionHasErrors('nominal');
});

it('pembalikan wajib punya alasan', function () {
    $uji = tahap6Lunas();
    $owner = akunPeranUji(Pengguna::PERAN_OWNER);

    $this->actingAs($owner)
        ->post(route('admin.pembayaran.pembalikan', bayarPertama($uji)), ['nominal' => 1000])
        ->assertSessionHasErrors('alasan');
});

it('pembayaran tidak bisa dibalik setelah barang diserahkan', function () {
    $uji = tahap6Lunas();
    $owner = akunPeranUji(Pengguna::PERAN_OWNER);

    app(SerahTerimaService::class)->serahkan($uji['servis'], [
        'nama_penerima' => 'Sari Pelanggan',
        'metode_verifikasi' => 'identitas',
    ], $uji['admin']);

    $asal = bayarPertama($uji);

    expect(fn () => app(PembayaranService::class)->pembalikan($asal, 1000, 'Batal', $owner))
        ->toThrow(ValidationException::class);
});
