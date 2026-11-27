<?php

use App\Models\Pengguna;
use App\Models\Servis;

it('teknisi tertugaskan bisa mulai pengerjaan', function () {
    $uji = tahap5Disetujui();

    $this->actingAs($uji['teknisi'])
        ->post(route('teknisi.pekerjaan.status', $uji['servis']), ['status' => 'dikerjakan'])
        ->assertRedirect(route('teknisi.pekerjaan.show', $uji['servis']))
        ->assertSessionHasNoErrors();

    expect($uji['servis']->refresh()->status)->toBe(Servis::DIKERJAKAN);
});

it('status tidak bisa melompat dari disetujui ke quality control', function () {
    $uji = tahap5Disetujui();

    $this->actingAs($uji['admin'])
        ->post(route('admin.servis.status', $uji['servis']), ['status' => 'quality_control'])
        ->assertSessionHasErrors('status');

    expect($uji['servis']->refresh()->status)->toBe(Servis::DISETUJUI);
});

it('teknisi yang tidak ditugaskan tidak bisa mengubah pengerjaan', function () {
    $uji = tahap5Dikerjakan();
    $lain = akunPeranUji(Pengguna::PERAN_TEKNISI);

    $this->actingAs($lain)
        ->post(route('teknisi.pekerjaan.status', $uji['servis']), ['status' => 'quality_control'])
        ->assertForbidden();

    expect($uji['servis']->refresh()->status)->toBe(Servis::DIKERJAKAN);
});

it('selesai perbaikan memindahkan servis ke quality control', function () {
    $uji = tahap5Dikerjakan();

    $this->actingAs($uji['teknisi'])
        ->post(route('teknisi.pekerjaan.status', $uji['servis']), ['status' => 'quality_control'])
        ->assertSessionHasNoErrors();

    expect($uji['servis']->refresh()->status)->toBe(Servis::QUALITY_CONTROL);
});

it('catatan pengerjaan tersimpan dengan penulisnya', function () {
    $uji = tahap5Dikerjakan();

    $this->actingAs($uji['teknisi'])
        ->post(route('teknisi.pekerjaan.catatan', $uji['servis']), ['isi' => 'LCD sudah diganti, uji sentuh normal.'])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('catatan_pengerjaan', [
        'id_servis' => $uji['servis']->id_servis,
        'id_pengguna' => $uji['teknisi']->id_pengguna,
        'isi' => 'LCD sudah diganti, uji sentuh normal.',
    ]);

    $this->actingAs($uji['teknisi'])
        ->get(route('teknisi.pekerjaan.show', $uji['servis']))
        ->assertOk()
        ->assertSee('LCD sudah diganti');
});

it('customer tidak bisa menulis catatan pengerjaan', function () {
    $uji = tahap5Dikerjakan();

    $this->actingAs($uji['customer'])
        ->post(route('admin.servis.catatan', $uji['servis']), ['isi' => 'coba'])
        ->assertForbidden();
});

it('halaman daftar pekerjaan admin menampilkan servis dan teknisinya', function () {
    $uji = tahap5Dikerjakan();

    $this->actingAs($uji['admin'])
        ->get(route('admin.pekerjaan.index'))
        ->assertOk()
        ->assertSee($uji['servis']->nomor_servis)
        ->assertSee($uji['teknisi']->nama);
});
