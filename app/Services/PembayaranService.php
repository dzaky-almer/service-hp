<?php

namespace App\Services;

use App\Contracts\PembayaranGateway;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Servis;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pembayaran, pembalikan, dan webhook (fitur 39–43).
 *
 * `pembayaran.status` hanya berubah di sini (ARCHITECTURE §3.3).
 * Semua perhitungan uang memakai baris terkunci agar dua pembayaran bersamaan
 * tidak melewati sisa tagihan.
 */
class PembayaranService
{
    public const MASA_KEDALUWARSA_JAM = 24;

    public function __construct(
        private readonly AuditService $audit,
        private readonly PembayaranGateway $gateway,
    ) {}

    /** Catat pembayaran baru berstatus menunggu (fitur 40). */
    public function buat(Servis $servis, float $nominal, string $metode, Pengguna $pelaku): Pembayaran
    {
        if (! array_key_exists($metode, Pembayaran::METODE)) {
            throw ValidationException::withMessages(['metode' => 'Metode pembayaran tidak dikenal.']);
        }

        return DB::transaction(function () use ($servis, $nominal, $metode, $pelaku): Pembayaran {
            $baris = Servis::whereKey($servis->id_servis)->lockForUpdate()->firstOrFail();

            if ($baris->status === Servis::DIBATALKAN) {
                throw ValidationException::withMessages(['nominal' => 'Servis dibatalkan, tidak ada tagihan.']);
            }

            if ($baris->status === Servis::SELESAI) {
                throw ValidationException::withMessages(['nominal' => 'Servis sudah selesai dan diserahkan.']);
            }

            if ($nominal <= 0) {
                throw ValidationException::withMessages(['nominal' => 'Nominal pembayaran harus lebih dari 0.']);
            }

            $tagihan = $baris->totalTagihan();
            $sisa = round($tagihan - $baris->totalDibayar(), 2);

            if ($tagihan <= 0) {
                throw ValidationException::withMessages(['nominal' => 'Servis ini belum punya tagihan.']);
            }

            if ($nominal > $sisa + 0.001) {
                throw ValidationException::withMessages([
                    'nominal' => 'Nominal melebihi sisa tagihan ('.rupiah($sisa).').',
                ]);
            }

            $sekarang = now();
            $pembayaran = new Pembayaran([
                'id_servis' => $baris->id_servis,
                'jenis' => Pembayaran::BAYAR,
                'metode' => $metode,
                'nominal' => $nominal,
                'status' => Pembayaran::MENUNGGU,
                'dibuat_oleh' => $pelaku->id_pengguna,
                'dibuat_pada' => $sekarang,
            ]);
            $pembayaran->save();

            if ($metode === 'gateway') {
                // Id transaksi unik; gateway simulasi tidak memanggil jaringan.
                $pembayaran->forceFill([
                    'id_transaksi_eksternal' => $this->gateway->buatTransaksi($pembayaran),
                ])->save();
            }

            $this->audit->catat('buat_pembayaran', $pembayaran, null, [
                'nominal' => (float) $pembayaran->nominal,
                'metode' => $metode,
                'status' => $pembayaran->status,
                'id_transaksi_eksternal' => $pembayaran->id_transaksi_eksternal,
            ], $pelaku);

            return $pembayaran;
        });
    }

    /** Konfirmasi manual (fitur 41): menunggu → berhasil. */
    public function konfirmasi(Pembayaran $pembayaran, Pengguna $pelaku): Pembayaran
    {
        return $this->putuskan($pembayaran, Pembayaran::BERHASIL, $pelaku, null, 'konfirmasi_pembayaran');
    }

    /** Tolak manual: menunggu → gagal. */
    public function tolak(Pembayaran $pembayaran, Pengguna $pelaku, ?string $alasan = null): Pembayaran
    {
        return $this->putuskan($pembayaran, Pembayaran::GAGAL, $pelaku, $alasan, 'tolak_pembayaran');
    }

    /**
     * Pembalikan (fitur 43): baris baru jenis pembalikan, bukan edit baris asal.
     * Admin hanya untuk pembayaran hari yang sama; selebihnya Owner (PRD lampiran 12).
     */
    public function pembalikan(Pembayaran $asal, float $nominal, string $alasan, Pengguna $pelaku): Pembayaran
    {
        if (trim($alasan) === '') {
            throw ValidationException::withMessages(['alasan' => 'Alasan pembalikan wajib diisi.']);
        }

        return DB::transaction(function () use ($asal, $nominal, $alasan, $pelaku): Pembayaran {
            $baris = Pembayaran::whereKey($asal->id_pembayaran)->lockForUpdate()->firstOrFail();

            if ($baris->jenis !== Pembayaran::BAYAR || $baris->status !== Pembayaran::BERHASIL) {
                throw ValidationException::withMessages(['nominal' => 'Hanya pembayaran berhasil yang dapat dibalik.']);
            }

            if ($baris->servis?->pengambilan()->exists()) {
                throw ValidationException::withMessages(['nominal' => 'Barang sudah diserahkan; pembayaran tidak dapat dibalik.']);
            }

            if ($pelaku->peran === Pengguna::PERAN_ADMIN && ! $baris->dibuat_pada->isToday()) {
                throw ValidationException::withMessages([
                    'nominal' => 'Pembalikan pembayaran hari lain hanya dapat dilakukan Owner.',
                ]);
            }

            if ($nominal <= 0) {
                throw ValidationException::withMessages(['nominal' => 'Nominal pembalikan harus lebih dari 0.']);
            }

            $sisa = round($baris->sisaDapatDibalik(), 2);

            if ($nominal > $sisa + 0.001) {
                throw ValidationException::withMessages([
                    'nominal' => 'Nominal melebihi yang belum dibalik ('.rupiah($sisa).').',
                ]);
            }

            $sekarang = now();
            $pembalikan = new Pembayaran([
                'id_servis' => $baris->id_servis,
                'jenis' => Pembayaran::PEMBALIKAN,
                'id_pembayaran_asal' => $baris->id_pembayaran,
                'metode' => $baris->metode,
                'nominal' => $nominal,
                'status' => Pembayaran::BERHASIL,
                'dibuat_oleh' => $pelaku->id_pengguna,
                'dikonfirmasi_oleh' => $pelaku->id_pengguna,
                'alasan' => trim($alasan),
                'dibuat_pada' => $sekarang,
                'diputuskan_pada' => $sekarang,
            ]);
            $pembalikan->save();

            $this->audit->catat('pembalikan_pembayaran', $pembalikan, null, [
                'id_pembayaran_asal' => $baris->id_pembayaran,
                'nominal' => $nominal,
                'alasan' => $pembalikan->alasan,
            ], $pelaku);

            return $pembalikan;
        });
    }

    /**
     * Webhook gateway (fitur 42). Payload selalu dicatat; efek hanya bila
     * tanda tangan HMAC valid dan baris cocok (SECURITY.md §5).
     */
    public function prosesWebhook(string $rawBody, ?string $tandaTangan, string $penyedia = 'simulasi'): WebhookLog
    {
        $data = json_decode($rawBody, true);
        $data = is_array($data) ? $data : [];

        $harapan = hash_hmac('sha256', $rawBody, (string) config('services.gateway.secret'));
        $valid = is_string($tandaTangan) && hash_equals($harapan, strtolower(trim($tandaTangan)));

        $log = WebhookLog::create([
            'penyedia' => $penyedia,
            'id_transaksi_eksternal' => $data['id_transaksi_eksternal'] ?? null,
            'payload' => $data,
            'tanda_tangan_valid' => $valid,
            'waktu' => now(),
        ]);

        if (! $valid || ! is_string($log->id_transaksi_eksternal)) {
            return $log;
        }

        DB::transaction(function () use ($log, $data): void {
            $baris = Pembayaran::where('id_transaksi_eksternal', $log->id_transaksi_eksternal)
                ->lockForUpdate()
                ->first();

            // Tidak dikenal / sudah final → idempoten, tidak ada efek ganda.
            if ($baris === null || $baris->status !== Pembayaran::MENUNGGU || $baris->metode !== 'gateway') {
                return;
            }

            $status = $data['status'] ?? null;

            if (! in_array($status, [Pembayaran::BERHASIL, Pembayaran::GAGAL], true)) {
                return;
            }

            // Nominal callback harus sama dengan yang dicatat.
            if (isset($data['nominal']) && abs((float) $data['nominal'] - (float) $baris->nominal) > 0.001) {
                return;
            }

            $baris->forceFill([
                'status' => $status,
                'dikonfirmasi_oleh' => null,
                'alasan' => $status === Pembayaran::GAGAL ? 'Ditolak gateway' : null,
                'diputuskan_pada' => now(),
            ])->save();

            $this->audit->catat('webhook_pembayaran', $baris, ['status' => Pembayaran::MENUNGGU], [
                'status' => $status,
                'id_log' => $log->id_log,
            ]);
        });

        return $log;
    }

    /** Command `pembayaran:kedaluwarsa` (ARCHITECTURE §3.5). Mengembalikan jumlah baris. */
    public function kedaluwarsa(): int
    {
        $batas = now()->subHours(self::MASA_KEDALUWARSA_JAM);

        $id = Pembayaran::query()
            ->where('metode', 'gateway')
            ->where('status', Pembayaran::MENUNGGU)
            ->where('dibuat_pada', '<', $batas)
            ->pluck('id_pembayaran');

        $jumlah = 0;

        foreach ($id as $kunci) {
            DB::transaction(function () use ($kunci, &$jumlah): void {
                $baris = Pembayaran::whereKey($kunci)->lockForUpdate()->first();

                if ($baris === null || $baris->status !== Pembayaran::MENUNGGU) {
                    return;
                }

                $baris->forceFill([
                    'status' => Pembayaran::GAGAL,
                    'alasan' => 'Kedaluwarsa (lebih dari '.self::MASA_KEDALUWARSA_JAM.' jam)',
                    'diputuskan_pada' => now(),
                ])->save();

                $this->audit->catat('kedaluwarsa_pembayaran', $baris, ['status' => Pembayaran::MENUNGGU], ['status' => Pembayaran::GAGAL]);
                $jumlah++;
            });
        }

        return $jumlah;
    }

    private function putuskan(Pembayaran $pembayaran, string $ke, Pengguna $pelaku, ?string $alasan, string $aksi): Pembayaran
    {
        return DB::transaction(function () use ($pembayaran, $ke, $pelaku, $alasan, $aksi): Pembayaran {
            $baris = Pembayaran::whereKey($pembayaran->id_pembayaran)->lockForUpdate()->firstOrFail();

            if ($baris->status !== Pembayaran::MENUNGGU) {
                throw ValidationException::withMessages(['status' => 'Pembayaran ini sudah diputuskan.']);
            }

            $baris->forceFill([
                'status' => $ke,
                'dikonfirmasi_oleh' => $pelaku->id_pengguna,
                'alasan' => $ke === Pembayaran::GAGAL ? ($alasan ?: 'Ditolak Admin') : $baris->alasan,
                'diputuskan_pada' => now(),
            ])->save();

            $this->audit->catat($aksi, $baris, ['status' => Pembayaran::MENUNGGU], ['status' => $ke], $pelaku);

            return $baris;
        });
    }
}
