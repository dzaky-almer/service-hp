<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Servis;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Angka untuk dashboard dan laporan (fitur 46–51). Query diambil dari
 * ARCHITECTURE §3.1 (nilai turunan) dan §D1–D3; tidak ada kolom ringkasan
 * yang disimpan, semua dihitung saat dibaca.
 */
class LaporanService
{
    /** Jumlah servis per status; status yang belum ada tetap muncul dengan nilai 0. */
    public function servisPerStatus(array $filter = []): array
    {
        $hasil = array_fill_keys(array_keys(Servis::LABEL_STATUS), 0);

        $baris = $this->saring(DB::table('servis'), $filter)
            ->selectRaw('status, COUNT(*) AS jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        foreach ($baris as $status => $jumlah) {
            $hasil[$status] = (int) $jumlah;
        }

        return $hasil;
    }

    public function jumlahTerlambat(array $filter = []): int
    {
        return $this->saring(DB::table('servis'), $filter)
            ->whereNotIn('status', [Servis::SIAP_DIAMBIL, Servis::SELESAI, Servis::DIBATALKAN, Servis::DITOLAK])
            ->whereRaw('DATE_ADD(tanggal_masuk, INTERVAL sla_hari_salinan DAY) < ?', [now()])
            ->count();
    }

    /** @return array{jumlah: int, rata_jam: float|null} */
    public function durasi(array $filter = []): array
    {
        $selesai = DB::table('riwayat_status_servis')
            ->selectRaw('id_servis, MIN(waktu) AS waktu_selesai')
            ->where('ke_status', Servis::SELESAI)
            ->groupBy('id_servis');

        $baris = $this->saring(DB::table('servis'), $filter)
            ->joinSub($selesai, 'selesai', 'selesai.id_servis', '=', 'servis.id_servis')
            ->selectRaw('COUNT(*) AS jumlah, AVG(TIMESTAMPDIFF(MINUTE, servis.tanggal_masuk, selesai.waktu_selesai)) AS rata_menit')
            ->first();

        return [
            'jumlah' => (int) ($baris->jumlah ?? 0),
            'rata_jam' => $baris->rata_menit === null ? null : round(((float) $baris->rata_menit) / 60, 1),
        ];
    }

    /**
     * Pendapatan bersih (bayar − pembalikan) per bulan, atau per hari bila `$harian`.
     *
     * @return list<array{periode: string, total: float}>
     */
    public function pendapatan(array $filter = [], bool $harian = false): array
    {
        $format = $harian ? '%Y-%m-%d' : '%Y-%m';

        $baris = $this->saring(DB::table('pembayaran'), $filter, kolomWaktu: 'diputuskan_pada')
            ->where('status', Pembayaran::BERHASIL)
            ->selectRaw("DATE_FORMAT(diputuskan_pada, '{$format}') AS periode,
                SUM(CASE WHEN jenis = ? THEN nominal ELSE -nominal END) AS total", [Pembayaran::BAYAR])
            ->groupBy('periode')
            ->orderBy('periode')
            ->get();

        return $baris->map(fn ($b) => ['periode' => $b->periode, 'total' => (float) $b->total])->all();
    }

    /** @return Collection<int, object> tugas, selesai, dan rata-rata jam per teknisi */
    public function performaTeknisi(array $filter = []): Collection
    {
        $selesai = DB::table('riwayat_status_servis')
            ->selectRaw('id_servis, MIN(waktu) AS waktu_selesai')
            ->where('ke_status', Servis::SELESAI)
            ->groupBy('id_servis');

        return $this->saring(DB::table('servis'), $filter)
            ->join('pengguna', 'pengguna.id_pengguna', '=', 'servis.id_teknisi')
            ->leftJoinSub($selesai, 'selesai', 'selesai.id_servis', '=', 'servis.id_servis')
            ->where('pengguna.peran', 'teknisi')
            ->selectRaw('pengguna.id_pengguna, pengguna.nama,
                COUNT(*) AS tugas,
                SUM(servis.status = ?) AS selesai,
                AVG(CASE WHEN selesai.waktu_selesai IS NULL THEN NULL
                    ELSE TIMESTAMPDIFF(MINUTE, servis.tanggal_masuk, selesai.waktu_selesai) END) AS rata_menit', [Servis::SELESAI])
            ->groupBy('pengguna.id_pengguna', 'pengguna.nama')
            ->orderByDesc('selesai')
            ->get()
            ->map(function ($b) {
                $b->selesai = (int) $b->selesai;
                $b->rata_jam = $b->rata_menit === null ? null : round(((float) $b->rata_menit) / 60, 1);

                return $b;
            });
    }

    /** @return Collection<int, object> spare part terpakai: jumlah, biaya (harga snapshot), sisa stok */
    public function statistikSparepart(array $filter = []): Collection
    {
        $stok = DB::table('mutasi_stok')->selectRaw('id_sparepart, SUM(jumlah) AS stok')->groupBy('id_sparepart');

        return $this->saring(DB::table('penggunaan_sparepart'), $filter, kolomWaktu: 'penggunaan_sparepart.waktu')
            ->join('sparepart', 'sparepart.id_sparepart', '=', 'penggunaan_sparepart.id_sparepart')
            ->leftJoinSub($stok, 'stok', 'stok.id_sparepart', '=', 'sparepart.id_sparepart')
            ->selectRaw('sparepart.sku, sparepart.nama,
                SUM(penggunaan_sparepart.jumlah) AS terpakai,
                SUM(penggunaan_sparepart.jumlah * penggunaan_sparepart.harga_beli_salinan) AS biaya,
                COALESCE(MAX(stok.stok), 0) AS stok')
            ->groupBy('sparepart.sku', 'sparepart.nama')
            ->orderByDesc('terpakai')
            ->get()
            ->map(function ($b) {
                $b->terpakai = (int) $b->terpakai;
                $b->biaya = (float) $b->biaya;
                $b->stok = (int) $b->stok;

                return $b;
            });
    }

    /** D3: kategori kerusakan terbanyak dari diagnosis versi terbaru tiap servis. */
    public function kerusakanTerbanyak(array $filter = []): Collection
    {
        $terbaru = DB::table('diagnosis')
            ->selectRaw('id_servis, MAX(versi) AS versi')
            ->groupBy('id_servis');

        return DB::table('diagnosis')
            ->joinSub($terbaru, 'terbaru', function ($j) {
                $j->on('terbaru.id_servis', '=', 'diagnosis.id_servis')->on('terbaru.versi', '=', 'diagnosis.versi');
            })
            ->when(isset($filter['dari']), fn ($q) => $q->where('diagnosis.waktu', '>=', $filter['dari']))
            ->when(isset($filter['sampai']), fn ($q) => $q->where('diagnosis.waktu', '<=', $filter['sampai']))
            ->selectRaw('diagnosis.kategori_kerusakan, COUNT(*) AS jumlah')
            ->groupBy('diagnosis.kategori_kerusakan')
            ->orderByDesc('jumlah')
            ->get()
            ->map(fn ($b) => ['kategori' => $b->kategori_kerusakan, 'jumlah' => (int) $b->jumlah]);
    }

    /** Ringkasan untuk kartu dashboard per peran. */
    public function ringkasan(?int $idTeknisi = null): array
    {
        $filter = $idTeknisi === null ? [] : ['id_teknisi' => $idTeknisi];

        return [
            'per_status' => $this->servisPerStatus($filter),
            'aktif' => array_sum(array_diff_key(
                $this->servisPerStatus($filter),
                array_flip([Servis::SELESAI, Servis::DIBATALKAN, Servis::DITOLAK]),
            )),
            'terlambat' => $this->jumlahTerlambat($filter),
            'durasi' => $this->durasi($filter),
            'pendapatan_hari_ini' => array_sum(array_column($this->pendapatan(
                ['dari' => now()->startOfDay(), 'sampai' => now()], harian: true,
            ), 'total')),
            'pendapatan_bulan_ini' => array_sum(array_column($this->pendapatan(
                ['dari' => now()->startOfMonth(), 'sampai' => now()],
            ), 'total')),
        ];
    }

    /**
     * Terapkan filter waktu/teknisi/status yang sama ke semua query laporan.
     *
     * @param  array{dari?: mixed, sampai?: mixed, id_teknisi?: int, status?: string}  $filter
     */
    private function saring(Builder $q, array $filter, string $kolomWaktu = 'tanggal_masuk'): Builder
    {
        $q->when(isset($filter['dari']), fn ($x) => $x->where($kolomWaktu, '>=', $filter['dari']))
            ->when(isset($filter['sampai']), fn ($x) => $x->where($kolomWaktu, '<=', $filter['sampai']));

        // id_teknisi/status hanya ada di tabel servis dan pembayaran (lewat relasi servis).
        if ($kolomWaktu === 'tanggal_masuk') {
            $q->when(isset($filter['id_teknisi']), fn ($x) => $x->where('id_teknisi', $filter['id_teknisi']))
                ->when(isset($filter['status']), fn ($x) => $x->where('status', $filter['status']));
        }

        return $q;
    }
}
