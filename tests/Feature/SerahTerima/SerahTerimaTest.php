<?php

use App\Models\Pengambilan;
use App\Models\Servis;
use App\Services\PembayaranService;

it('serah terima ditolak selama tagihan belum lunas', function () {
    $uji = tahap6SiapDiambil();
    $pembayaran = app(PembayaranService::class);
    $bayar = $pembayaran->buat($uji['servis'], 1000, 'tunai', $uji['admin']);
    $pembayaran->konfirmasi($bayar, $uji['admin']);

    $this->actingAs($uji['admin'])
        ->post(route('admin.servis.serah-terima', $uji['servis']), [
            'nama_penerima' => 'Sari Pelanggan',
            'metode_verifikasi' => 'kode_tracking',
        ])
        ->assertSessionHasErrors('nama_penerima');

    expect($uji['servis']->refresh()->status)->toBe(Servis::SIAP_DIAMBIL)
        ->and(Pengambilan::count())->toBe(0);
});

it('serah terima lunas menutup servis dan membuat bukti pengambilan', function () {
    $uji = tahap6Lunas();

    $this->actingAs($uji['admin'])
        ->post(route('admin.servis.serah-terima', $uji['servis']), [
            'nama_penerima' => 'Sari Pelanggan',
            'metode_verifikasi' => 'kode_tracking',
            'catatan' => 'Diambil sendiri',
        ])
        ->assertRedirect(route('admin.servis.show', $uji['servis']))
        ->assertSessionHasNoErrors();

    $uji['servis']->refresh();
    $pengambilan = Pengambilan::sole();

    expect($uji['servis']->status)->toBe(Servis::SELESAI)
        ->and($pengambilan->id_servis)->toBe($uji['servis']->id_servis)
        ->and($pengambilan->nama_penerima)->toBe('Sari Pelanggan')
        ->and($pengambilan->diserahkan_oleh)->toBe($uji['admin']->id_pengguna)
        ->and($uji['servis']->riwayat()->where('ke_status', Servis::SELESAI)->exists())->toBeTrue();

    $respons = $this->actingAs($uji['admin'])->get(route('admin.servis.bukti-pengambilan', $uji['servis']));

    $respons->assertOk();
    expect($respons->headers->get('content-type'))->toContain('application/pdf');
});

it('barang tidak bisa diserahkan dua kali', function () {
    $uji = tahap6Lunas();
    $data = ['nama_penerima' => 'Sari Pelanggan', 'metode_verifikasi' => 'identitas'];

    $this->actingAs($uji['admin'])->post(route('admin.servis.serah-terima', $uji['servis']), $data);
    $this->actingAs($uji['admin'])
        ->post(route('admin.servis.serah-terima', $uji['servis']), $data)
        ->assertSessionHasErrors('nama_penerima');

    expect(Pengambilan::count())->toBe(1);
});

it('serah terima butuh nama penerima', function () {
    $uji = tahap6Lunas();

    $this->actingAs($uji['admin'])
        ->post(route('admin.servis.serah-terima', $uji['servis']), ['metode_verifikasi' => 'identitas'])
        ->assertSessionHasErrors('nama_penerima');
});

it('bukti pengambilan hanya tersedia bagi staf dan setelah barang diserahkan', function () {
    $uji = tahap6Lunas();

    $this->actingAs($uji['admin'])
        ->get(route('admin.servis.bukti-pengambilan', $uji['servis']))
        ->assertNotFound();

    $this->actingAs($uji['customer'])
        ->get(route('admin.servis.bukti-pengambilan', $uji['servis']))
        ->assertForbidden();
});
