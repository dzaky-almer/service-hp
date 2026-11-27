<?php

use App\Models\FotoServis;
use App\Models\Pelanggan;
use App\Models\PengaturanBisnis;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Services\ServisService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/*
| Tahap 3 — service order (fitur 15–22): buat + nomor/kode otomatis, snapshot SLA,
| cari/daftar, ubah info, foto, assign teknisi, riwayat status, batal, izin per peran.
*/

function pelangganServisUji(?Pengguna $akun = null): Pelanggan
{
    return Pelanggan::create([
        'id_pengguna' => $akun?->id_pengguna,
        'nama' => 'Sari Pelanggan',
        'no_hp' => '0813'.random_int(1000000, 9999999),
        'email' => 'sari'.random_int(1000, 9999).'@example.com',
        'alamat' => 'Jl. Melati 9',
    ]);
}

function dataServisUji(array $ubah = []): array
{
    return $ubah + [
        'jenis_perangkat' => 'laptop',
        'merek' => 'Asus',
        'model' => 'VivoBook 14',
        'nomor_seri' => 'SN-123',
        'keluhan' => 'Tidak menyala setelah kena air',
        'kondisi_fisik' => 'Bodi penyok sudut kiri',
        'kelengkapan' => 'Charger',
    ];
}

function servisAdminUji(): Pengguna
{
    return Pengguna::factory()->create(['peran' => Pengguna::PERAN_ADMIN]);
}

function buatServisUji(Pengguna $admin, ?Pelanggan $pelanggan = null): Servis
{
    return app(ServisService::class)->buatServis(
        dataServisUji(['id_pelanggan' => ($pelanggan ?? pelangganServisUji())->id_pelanggan]),
        $admin,
    );
}

test('admin membuat servis: nomor tahun berjalan, kode tracking, SLA tersalin, riwayat awal', function () {
    PengaturanBisnis::updateOrCreate(['kunci' => 'sla_hari'], ['nilai' => '3']);
    $admin = servisAdminUji();
    $pelanggan = pelangganServisUji();

    $this->actingAs($admin)
        ->post(route('admin.servis.store'), dataServisUji(['id_pelanggan' => $pelanggan->id_pelanggan]))
        ->assertSessionHasNoErrors();

    $servis = Servis::sole();

    expect($servis->nomor_servis)->toMatch('/^SRV-\d{4}-00001$/')
        ->and($servis->kode_tracking)->toHaveLength(12)
        ->and($servis->status)->toBe(Servis::DITERIMA)
        ->and($servis->sla_hari_salinan)->toBe(3)
        ->and($servis->dibuat_oleh)->toBe($admin->id_pengguna)
        ->and($servis->riwayat)->toHaveCount(1)
        ->and($servis->riwayat->first()->dari_status)->toBeNull()
        ->and($servis->riwayat->first()->ke_status)->toBe(Servis::DITERIMA);

    expect(DB::table('audit_log')->where('aksi', 'buat_servis')->count())->toBe(1);
});

test('nomor servis berurutan per tahun', function () {
    $admin = servisAdminUji();
    $pelanggan = pelangganServisUji();

    $pertama = buatServisUji($admin, $pelanggan);
    $kedua = buatServisUji($admin, $pelanggan);

    expect((int) substr($pertama->nomor_servis, -5))->toBe(1)
        ->and((int) substr($kedua->nomor_servis, -5))->toBe(2)
        ->and($pertama->kode_tracking)->not->toBe($kedua->kode_tracking);
});

test('servis tidak bisa dibuat untuk pelanggan nonaktif', function () {
    $admin = servisAdminUji();
    $pelanggan = pelangganServisUji();
    DB::table('pelanggan')->where('id_pelanggan', $pelanggan->id_pelanggan)->update(['status' => 'nonaktif']);

    $this->actingAs($admin)
        ->post(route('admin.servis.store'), dataServisUji(['id_pelanggan' => $pelanggan->id_pelanggan]))
        ->assertSessionHasErrors('id_pelanggan');

    expect(Servis::count())->toBe(0);
});

test('admin mengubah informasi servis tanpa mengubah status', function () {
    $admin = servisAdminUji();
    $servis = buatServisUji($admin);

    $this->actingAs($admin)
        ->put(route('admin.servis.update', $servis), dataServisUji(['merek' => 'Lenovo', 'keluhan' => 'Mati total']))
        ->assertSessionHasNoErrors();

    $servis->refresh();
    expect($servis->merek)->toBe('Lenovo')
        ->and($servis->keluhan)->toBe('Mati total')
        ->and($servis->status)->toBe(Servis::DITERIMA)
        ->and(DB::table('audit_log')->where('aksi', 'ubah_servis')->count())->toBe(1);
});

test('assign teknisi memindahkan servis ke menunggu diagnosis', function () {
    $admin = servisAdminUji();
    $teknisi = Pengguna::factory()->create(['peran' => Pengguna::PERAN_TEKNISI]);
    $servis = buatServisUji($admin);

    $this->actingAs($admin)
        ->post(route('admin.servis.teknisi', $servis), ['id_teknisi' => $teknisi->id_pengguna])
        ->assertSessionHasNoErrors();

    $servis->refresh();
    expect($servis->id_teknisi)->toBe($teknisi->id_pengguna)
        ->and($servis->status)->toBe(Servis::MENUNGGU_DIAGNOSIS)
        ->and($servis->riwayat)->toHaveCount(2);
});

test('assign akun non-teknisi ditolak', function () {
    $admin = servisAdminUji();
    $lain = Pengguna::factory()->create(['peran' => Pengguna::PERAN_ADMIN]);
    $servis = buatServisUji($admin);

    $this->actingAs($admin)
        ->post(route('admin.servis.teknisi', $servis), ['id_teknisi' => $lain->id_pengguna])
        ->assertSessionHasErrors('id_teknisi');

    expect($servis->refresh()->id_teknisi)->toBeNull();
});

test('pembatalan: alasan wajib, admin terbatas status awal, owner bisa di status lanjut', function () {
    $admin = servisAdminUji();
    $owner = Pengguna::factory()->create(['peran' => Pengguna::PERAN_OWNER]);
    $servis = buatServisUji($admin);

    $this->actingAs($admin)
        ->post(route('admin.servis.batal', $servis), ['alasan' => '  '])
        ->assertSessionHasErrors('alasan');

    // Diterima → admin boleh membatalkan.
    $this->actingAs($admin)
        ->post(route('admin.servis.batal', $servis), ['alasan' => 'Pelanggan salah bawa unit'])
        ->assertSessionHasNoErrors();

    $servis->refresh();
    expect($servis->status)->toBe(Servis::DIBATALKAN)
        ->and($servis->riwayat->last()->alasan)->toBe('Pelanggan salah bawa unit');

    // Status akhir tidak bisa dibatalkan lagi.
    $this->actingAs($owner)
        ->post(route('admin.servis.batal', $servis), ['alasan' => 'lagi'])
        ->assertSessionHasErrors('status');

    // Status lanjut: admin ditolak, owner boleh.
    $servisDua = buatServisUji($admin);
    app(ServisService::class)->pindahStatus($servisDua, Servis::MENUNGGU_DIAGNOSIS, $admin);
    app(ServisService::class)->pindahStatus($servisDua, Servis::MENUNGGU_PERSETUJUAN, $admin);
    app(ServisService::class)->pindahStatus($servisDua, Servis::DISETUJUI, $admin);
    app(ServisService::class)->pindahStatus($servisDua, Servis::DIKERJAKAN, $admin);

    $this->actingAs($admin)
        ->post(route('admin.servis.batal', $servisDua), ['alasan' => 'admin coba'])
        ->assertSessionHasErrors('status');

    $this->actingAs($owner)
        ->post(route('admin.servis.batal', $servisDua), ['alasan' => 'spare part tidak tersedia'])
        ->assertSessionHasNoErrors();

    expect($servisDua->refresh()->status)->toBe(Servis::DIBATALKAN);
});

test('transisi status di luar peta ditolak', function () {
    $admin = servisAdminUji();
    $servis = buatServisUji($admin);

    app(ServisService::class)->pindahStatus($servis, Servis::SELESAI, $admin);
})->throws(ValidationException::class);

test('izin detail servis: pemilik, teknisi yang ditugaskan, dan staf', function () {
    $admin = servisAdminUji();
    $akunPelanggan = Pengguna::factory()->create(['peran' => Pengguna::PERAN_CUSTOMER]);
    $pelanggan = pelangganServisUji($akunPelanggan);
    $servis = buatServisUji($admin, $pelanggan);

    $pemilik = Pengguna::factory()->create(['peran' => Pengguna::PERAN_CUSTOMER]);
    pelangganServisUji($pemilik);

    $teknisiLain = Pengguna::factory()->create(['peran' => Pengguna::PERAN_TEKNISI]);
    $teknisi = Pengguna::factory()->create(['peran' => Pengguna::PERAN_TEKNISI]);
    app(ServisService::class)->assignTeknisi($servis, $teknisi, $admin);

    $this->actingAs($admin)->get(route('admin.servis.show', $servis))->assertOk();
    $this->actingAs($akunPelanggan)->get(route('pelanggan.servis.show', $servis))->assertOk();
    $this->actingAs($pemilik)->get(route('pelanggan.servis.show', $servis))->assertForbidden();
    $this->actingAs($teknisi)->get(route('teknisi.pekerjaan.show', $servis))->assertOk();
    $this->actingAs($teknisiLain)->get(route('teknisi.pekerjaan.show', $servis))->assertForbidden();
});

test('foto servis tersimpan di disk privat dan hanya bisa diakses yang berhak', function () {
    Storage::fake('local');
    $admin = servisAdminUji();
    $servis = buatServisUji($admin);

    $this->actingAs($admin)
        ->post(route('admin.servis.foto', $servis), [
            'foto' => UploadedFile::fake()->image('rusak.jpg'),
            'jenis' => 'masuk',
        ])
        ->assertSessionHasNoErrors();

    $foto = FotoServis::sole();
    expect($foto->path_file)->toStartWith('servis/'.$servis->id_servis.'/')
        ->and(Storage::disk('local')->exists($foto->path_file))->toBeTrue();

    $this->actingAs($admin)->get(route('foto.show', $foto))->assertOk();

    $asing = Pengguna::factory()->create(['peran' => Pengguna::PERAN_TEKNISI]);
    $this->actingAs($asing)->get(route('foto.show', $foto))->assertForbidden();
});

test('unggahan yang bukan gambar ditolak dan tidak menyentuh disk', function () {
    Storage::fake('local');
    $admin = servisAdminUji();
    $servis = buatServisUji($admin);

    $berkas = tempnam(sys_get_temp_dir(), 'uji').'.jpg';
    file_put_contents($berkas, '<?php echo "x"; ?>');

    $this->actingAs($admin)
        ->post(route('admin.servis.foto', $servis), [
            'foto' => new UploadedFile($berkas, 'skrip.jpg', 'image/jpeg', null, true),
            'jenis' => 'masuk',
        ])
        ->assertSessionHasErrors('foto');

    expect(FotoServis::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('tanda terima servis dapat dicetak sebagai PDF', function () {
    $admin = servisAdminUji();
    $servis = buatServisUji($admin);

    $respons = $this->actingAs($admin)->get(route('admin.servis.tanda-terima', $servis));

    $respons->assertOk();
    expect($respons->headers->get('content-type'))->toContain('application/pdf');
});
