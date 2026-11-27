<?php

namespace App\Contracts;

use App\Models\Pembayaran;

/** Perantara pembayaran eksternal (fitur 42). Implementasi nyata diganti di sini. */
interface PembayaranGateway
{
    public function nama(): string;

    /** Buat transaksi di penyedia; kembalikan id transaksi eksternal. */
    public function buatTransaksi(Pembayaran $pembayaran): string;
}
