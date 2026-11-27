<?php

use App\Models\Estimasi;
use App\Models\Pengguna;
use App\Models\Servis;
use Illuminate\Support\Facades\DB;

/*
| Tahap 4 — fitur 27: customer pemilik menyetujui / menolak estimasi.
*/

function tahap4PelangganDenganAkun(): array
{
    $akun = akunPeranUji(Pengguna::PERAN_CUSTOMER);

    return [$akun, tahap4Pelanggan($akun)];
}

it('customer pemilik menyetujui estimasi: servis jadi disetujui dan keputusan tercatat', function () {
    [$akun, $pelanggan] = tahap4PelangganDenganAkun();
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, $pelanggan);
    $estimasi = tahap4EstimasiTerkirim($servis, $admin);

    $this->actingAs($akun)
        ->post(route('pelanggan.servis.estimasi.setujui', [$servis, $estimasi]), [
            'catatan' => 'Setuju, lanjutkan.',
        ])->assertRedirect(route('pelanggan.servis.show', $servis));

    expect($estimasi->refresh()->status)->toBe(Estimasi::DISETUJUI)
        ->and($estimasi->diputuskan_oleh)->toBe($akun->id_pengguna)
        ->and($estimasi->diputuskan_pada)->not->toBeNull()
        ->and($servis->refresh()->status)->toBe(Servis::DISETUJUI)
        ->and(DB::table('audit_log')->where('aksi', 'setujui_estimasi')->where('objek_tipe', 'Servis')->where('objek_id', $servis->id_servis)->count())->toBe(1);
});

it('customer pemilik menolak estimasi: servis jadi ditolak dan catatan tersimpan', function () {
    [$akun, $pelanggan] = tahap4PelangganDenganAkun();
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, $pelanggan);
    $estimasi = tahap4EstimasiTerkirim($servis, $admin);

    $this->actingAs($akun)
        ->post(route('pelanggan.servis.estimasi.tolak', [$servis, $estimasi]), [
            'catatan' => 'Terlalu mahal, saya ambil perangkatnya.',
        ])->assertRedirect(route('pelanggan.servis.show', $servis));

    expect($estimasi->refresh()->status)->toBe(Estimasi::DITOLAK)
        ->and($estimasi->catatan_customer)->toBe('Terlalu mahal, saya ambil perangkatnya.')
        ->and($servis->refresh()->status)->toBe(Servis::DITOLAK)
        ->and($servis->totalTagihan())->toBe((float) $servis->biaya_pemeriksaan_salinan);
});

it('customer lain tidak boleh memutuskan estimasi', function () {
    [, $pelanggan] = tahap4PelangganDenganAkun();
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, $pelanggan);
    $estimasi = tahap4EstimasiTerkirim($servis, $admin);

    $orangLain = akunPeranUji(Pengguna::PERAN_CUSTOMER);

    $this->actingAs($orangLain)
        ->post(route('pelanggan.servis.estimasi.setujui', [$servis, $estimasi]))
        ->assertForbidden();

    expect($estimasi->refresh()->status)->toBe(Estimasi::DIKIRIM);
});

it('keputusan kedua ditolak dan status tidak berubah', function () {
    [$akun, $pelanggan] = tahap4PelangganDenganAkun();
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, $pelanggan);
    $estimasi = tahap4EstimasiTerkirim($servis, $admin);

    $this->actingAs($akun)->post(route('pelanggan.servis.estimasi.setujui', [$servis, $estimasi]));
    $this->actingAs($akun)->post(route('pelanggan.servis.estimasi.tolak', [$servis, $estimasi]))
        ->assertSessionHasErrors('estimasi');

    expect($estimasi->refresh()->status)->toBe(Estimasi::DISETUJUI)
        ->and($servis->refresh()->status)->toBe(Servis::DISETUJUI);
});

it('admin dan teknisi tidak boleh memutuskan estimasi', function () {
    [, $pelanggan] = tahap4PelangganDenganAkun();
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $teknisi = akunPeranUji(Pengguna::PERAN_TEKNISI);
    $servis = tahap4Servis($admin, $pelanggan, $teknisi);
    $estimasi = tahap4EstimasiTerkirim($servis, $admin);

    $this->actingAs($teknisi)
        ->post(route('pelanggan.servis.estimasi.setujui', [$servis, $estimasi]))
        ->assertForbidden();

    expect($estimasi->refresh()->status)->toBe(Estimasi::DIKIRIM);
});

it('halaman pelanggan menampilkan diagnosis, rincian estimasi, dan tombol keputusan', function () {
    [$akun, $pelanggan] = tahap4PelangganDenganAkun();
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, $pelanggan);
    tahap4EstimasiTerkirim($servis, $admin);

    $this->actingAs($akun)->get(route('pelanggan.servis.show', $servis))
        ->assertOk()
        ->assertSee('Panel LCD retak, perlu diganti.')
        ->assertSee('Ganti LCD')
        ->assertSee('Setujui estimasi')
        ->assertSee('Tolak estimasi');
});
