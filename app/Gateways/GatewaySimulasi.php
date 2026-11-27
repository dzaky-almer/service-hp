<?php

namespace App\Gateways;

use App\Contracts\PembayaranGateway;
use App\Models\Pembayaran;
use Illuminate\Support\Str;

/**
 * Gateway simulasi (PRD lampiran: "gateway simulasi"). Tidak menghubungi jaringan;
 * cukup membuat id transaksi yang nanti dikirim balik lewat webhook.
 */
class GatewaySimulasi implements PembayaranGateway
{
    public function nama(): string
    {
        return 'simulasi';
    }

    public function buatTransaksi(Pembayaran $pembayaran): string
    {
        return 'SIM-'.$pembayaran->id_pembayaran.'-'.Str::upper(Str::random(8));
    }
}
