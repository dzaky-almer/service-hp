<?php

use App\Models\Pengguna;
use App\Models\Servis;
use App\Services\PengerjaanService;
use App\Services\ServisService;

function tahap5Qc(): array
{
    $uji = tahap5Dikerjakan();
    $uji['servis'] = app(ServisService::class)->pindahStatus($uji['servis']->refresh(), Servis::QUALITY_CONTROL, $uji['teknisi']);

    return $uji;
}

function butirQc(string $gagal = ''): array
{
    $hasil = [];
    foreach (PengerjaanService::BUTIR_QC as $butir) {
        $hasil[$butir] = $butir === $gagal ? 'tidak_lulus' : 'lulus';
    }

    return $hasil;
}

it('QC lulus semua mencatat satu putaran lalu bisa ditandai siap diambil', function () {
    $uji = tahap5Qc();

    $this->actingAs($uji['teknisi'])
        ->post(route('teknisi.pekerjaan.qc', $uji['servis']), ['butir' => butirQc()])
        ->assertSessionHasNoErrors();

    expect($uji['servis']->refresh()->status)->toBe(Servis::QUALITY_CONTROL);
    expect($uji['servis']->qc()->where('putaran', 1)->count())->toBe(count(PengerjaanService::BUTIR_QC));
    expect($uji['servis']->qcLulusTerakhir())->toBeTrue();

    $this->actingAs($uji['admin'])
        ->post(route('admin.servis.siap-diambil', $uji['servis']))
        ->assertSessionHasNoErrors();

    expect($uji['servis']->refresh()->status)->toBe(Servis::SIAP_DIAMBIL);
});

it('butir gagal mengembalikan servis ke pengerjaan dan putaran berikutnya naik', function () {
    $uji = tahap5Qc();

    $this->actingAs($uji['teknisi'])
        ->post(route('teknisi.pekerjaan.qc', $uji['servis']), ['butir' => butirQc('Layar')])
        ->assertSessionHasNoErrors();

    expect($uji['servis']->refresh()->status)->toBe(Servis::DIKERJAKAN);
    expect($uji['servis']->qc()->where('lulus', false)->value('butir'))->toBe('Layar');

    // Perbaiki lalu kirim QC lagi: putaran baru, bukan menimpa putaran 1.
    $servis = app(ServisService::class)->pindahStatus($uji['servis']->refresh(), Servis::QUALITY_CONTROL, $uji['teknisi']);

    $this->actingAs($uji['teknisi'])
        ->post(route('teknisi.pekerjaan.qc', $servis), ['butir' => butirQc()])
        ->assertSessionHasNoErrors();

    expect($servis->refresh()->qcPutaranTerakhir())->toBe(2);
    expect($servis->qcLulusTerakhir())->toBeTrue();
});

it('siap diambil ditolak bila QC belum pernah diisi', function () {
    $uji = tahap5Qc();

    $this->actingAs($uji['admin'])
        ->post(route('admin.servis.siap-diambil', $uji['servis']))
        ->assertSessionHasErrors('status');

    expect($uji['servis']->refresh()->status)->toBe(Servis::QUALITY_CONTROL);
});

it('QC ditolak sebelum servis masuk tahap quality control', function () {
    $uji = tahap5Dikerjakan();

    $this->actingAs($uji['teknisi'])
        ->post(route('teknisi.pekerjaan.qc', $uji['servis']), ['butir' => butirQc()])
        ->assertSessionHasErrors('status');

    expect($uji['servis']->refresh()->qc()->count())->toBe(0);
});

it('teknisi lain tidak bisa mengisi QC', function () {
    $uji = tahap5Qc();
    $lain = akunPeranUji(Pengguna::PERAN_TEKNISI);

    $this->actingAs($lain)
        ->post(route('teknisi.pekerjaan.qc', $uji['servis']), ['butir' => butirQc()])
        ->assertForbidden();
});
