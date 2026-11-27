<?php

use App\Models\Pengguna;
use App\Models\Servis;

it('servis melewati SLA ditandai terlambat, tepat di batas belum', function () {
    $this->travelTo(now()->startOfSecond());

    $admin = akunPeranUji(Pengguna::PERAN_ADMIN);
    $teknisi = akunPeranUji(Pengguna::PERAN_TEKNISI);

    $tepat = tahap4Servis($admin, tahap4Pelanggan());
    $tepat->forceFill([
        'tanggal_masuk' => now()->subDays(7),
        'sla_hari_salinan' => 7,
        'id_teknisi' => $teknisi->id_pengguna,
        'status' => Servis::DIKERJAKAN,
    ])->save();

    $lewat = tahap4Servis($admin, tahap4Pelanggan());
    $lewat->forceFill([
        'tanggal_masuk' => now()->subDays(7)->subMinute(),
        'sla_hari_salinan' => 7,
        'id_teknisi' => $teknisi->id_pengguna,
        'status' => Servis::DIKERJAKAN,
    ])->save();

    // Status akhir tidak pernah dianggap terlambat.
    $selesai = tahap4Servis($admin, tahap4Pelanggan());
    $selesai->forceFill([
        'tanggal_masuk' => now()->subDays(30),
        'sla_hari_salinan' => 7,
        'status' => Servis::SIAP_DIAMBIL,
    ])->save();

    expect($tepat->terlambat())->toBeFalse()
        ->and($lewat->terlambat())->toBeTrue()
        ->and($selesai->terlambat())->toBeFalse()
        ->and(Servis::query()->terlambat()->pluck('id_servis')->all())->toBe([$lewat->id_servis]);
});
