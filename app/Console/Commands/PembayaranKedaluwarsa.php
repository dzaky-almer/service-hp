<?php

namespace App\Console\Commands;

use App\Services\PembayaranService;
use Illuminate\Console\Command;

/** Tandai pembayaran gateway yang menggantung lebih dari masa kedaluwarsa jadi gagal. */
class PembayaranKedaluwarsa extends Command
{
    protected $signature = 'pembayaran:kedaluwarsa';

    protected $description = 'Tandai pembayaran gateway yang kedaluwarsa sebagai gagal';

    public function handle(PembayaranService $pembayaran): int
    {
        $jumlah = $pembayaran->kedaluwarsa();

        $this->info("{$jumlah} pembayaran ditandai gagal karena kedaluwarsa.");

        return self::SUCCESS;
    }
}
