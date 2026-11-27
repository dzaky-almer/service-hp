<?php

namespace App\Services;

use App\Models\Pengambilan;
use App\Models\Pengguna;
use App\Models\Servis;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Serah terima barang (fitur 44): syarat lunas + siap diambil, sekali saja. */
class SerahTerimaService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly ServisService $servis,
    ) {}

    /** @param array{nama_penerima:string, metode_verifikasi:string, catatan?:?string} $data */
    public function serahkan(Servis $servis, array $data, Pengguna $pelaku): Pengambilan
    {
        return DB::transaction(function () use ($servis, $data, $pelaku): Pengambilan {
            $baris = Servis::whereKey($servis->id_servis)->lockForUpdate()->firstOrFail();

            if ($baris->status !== Servis::SIAP_DIAMBIL) {
                throw ValidationException::withMessages([
                    'nama_penerima' => 'Barang belum berstatus Siap Diambil (sekarang '.$baris->labelStatus().').',
                ]);
            }

            if (! $baris->lunas()) {
                throw ValidationException::withMessages([
                    'nama_penerima' => 'Tagihan belum lunas; sisa '.rupiah($baris->sisaTagihan()).'.',
                ]);
            }

            if ($baris->pengambilan()->exists()) {
                throw ValidationException::withMessages(['nama_penerima' => 'Barang sudah pernah diserahkan.']);
            }

            $pengambilan = Pengambilan::create([
                'id_servis' => $baris->id_servis,
                'diserahkan_oleh' => $pelaku->id_pengguna,
                'nama_penerima' => $data['nama_penerima'],
                'metode_verifikasi' => $data['metode_verifikasi'],
                'catatan' => $data['catatan'] ?? null,
                'waktu' => now(),
            ]);

            $this->servis->pindahStatus($baris, Servis::SELESAI, $pelaku, 'Diserahkan kepada '.$pengambilan->nama_penerima);

            $this->audit->catat('serah_terima', $pengambilan, null, [
                'nama_penerima' => $pengambilan->nama_penerima,
                'metode_verifikasi' => $pengambilan->metode_verifikasi,
            ], $pelaku);

            return $pengambilan;
        });
    }
}
