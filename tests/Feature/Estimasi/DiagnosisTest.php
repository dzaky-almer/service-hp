<?php

use App\Models\Diagnosis;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Services\ServisService;

/*
| Tahap 4 — fitur 23: diagnosis teknisi, append-only per versi (BR-11).
*/

it('teknisi yang ditugaskan mencatat diagnosis sebagai versi berurutan tanpa mengubah versi lama', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $teknisi = akunPeranUji(Pengguna::PERAN_TEKNISI);
    $servis = tahap4Servis($admin, tahap4Pelanggan(), $teknisi);

    $this->actingAs($teknisi)->post(route('teknisi.pekerjaan.diagnosis', $servis), [
        'kategori_kerusakan' => 'Kerusakan Layar',
        'hasil' => 'Panel LCD retak, perlu diganti.',
    ])->assertRedirect(route('teknisi.pekerjaan.show', $servis));

    $this->actingAs($teknisi)->post(route('teknisi.pekerjaan.diagnosis', $servis), [
        'kategori_kerusakan' => 'kerusakan layar',
        'hasil' => 'Ternyata kabel fleksibel juga putus.',
    ]);

    $daftar = Diagnosis::where('id_servis', $servis->id_servis)->orderBy('versi')->get();

    expect($daftar)->toHaveCount(2)
        ->and($daftar[0]->versi)->toBe(1)
        ->and($daftar[0]->hasil)->toBe('Panel LCD retak, perlu diganti.')
        ->and($daftar[0]->kategori_kerusakan)->toBe('kerusakan layar')
        ->and($daftar[1]->versi)->toBe(2)
        ->and($daftar[1]->kategori_kerusakan)->toBe('kerusakan layar')
        ->and($daftar[1]->dibuat_oleh)->toBe($teknisi->id_pengguna);
});

it('diagnosis wajib punya kategori dan hasil', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());

    $this->actingAs($admin)->post(route('admin.servis.diagnosis', $servis), [
        'kategori_kerusakan' => '',
        'hasil' => '',
    ])->assertSessionHasErrors(['kategori_kerusakan', 'hasil']);

    expect(Diagnosis::count())->toBe(0);
});

it('teknisi yang tidak ditugaskan dan customer tidak boleh mencatat diagnosis', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $teknisiLain = akunPeranUji(Pengguna::PERAN_TEKNISI);
    $servis = tahap4Servis($admin, tahap4Pelanggan(), akunPeranUji(Pengguna::PERAN_TEKNISI));

    $this->actingAs($teknisiLain)->post(route('teknisi.pekerjaan.diagnosis', $servis), [
        'kategori_kerusakan' => 'layar',
        'hasil' => 'Coba-coba.',
    ])->assertForbidden();

    $customer = $servis->pelanggan->pengguna;

    $this->actingAs($customer)->post(route('admin.servis.diagnosis', $servis), [
        'kategori_kerusakan' => 'layar',
        'hasil' => 'Coba-coba.',
    ])->assertForbidden();

    expect(Diagnosis::count())->toBe(0);
});

it('diagnosis ditolak saat servis sudah menunggu persetujuan', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan());

    app(ServisService::class)->pindahStatus($servis, Servis::MENUNGGU_PERSETUJUAN, $admin);

    $this->actingAs($admin)->post(route('admin.servis.diagnosis', $servis->refresh()), [
        'kategori_kerusakan' => 'layar',
        'hasil' => 'Terlambat.',
    ])->assertSessionHasErrors('hasil');

    expect(Diagnosis::count())->toBe(0);
});

it('servis diterima masuk tahap diagnosis lewat aksi mulai diagnosis', function () {
    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $servis = tahap4Servis($admin, tahap4Pelanggan(), null, false);

    $this->actingAs($admin)->post(route('admin.servis.diagnosis.mulai', $servis))
        ->assertRedirect(route('admin.servis.show', $servis));

    expect($servis->refresh()->status)->toBe(Servis::MENUNGGU_DIAGNOSIS);
});
