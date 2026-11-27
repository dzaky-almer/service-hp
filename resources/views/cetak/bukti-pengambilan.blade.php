<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Pengambilan {{ $servis->nomor_servis }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #111; margin: 24px; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 5px 6px; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { width: 34%; color: #555; font-weight: normal; }
        .box { border: 1px solid #bbb; padding: 8px; margin-top: 12px; }
        .qr { float: right; text-align: center; }
        .ttd { margin-top: 40px; width: 100%; }
        .ttd td { border: none; text-align: center; }
        .garis { border-top: 1px solid #333; margin-top: 48px; padding-top: 4px; }
        .kanan { text-align: right; }
    </style>
</head>
<body>
    <div class="qr">
        {!! $qr !!}
        <div class="muted" style="font-size:9px">Lacak: {{ $servis->kode_tracking }}</div>
    </div>

    <h1>{{ $bisnis['nama'] }}</h1>
    <div class="muted">{{ $bisnis['alamat'] }}</div>
    <div style="margin-top:12px; font-size:13px; font-weight:bold">BUKTI PENGAMBILAN BARANG</div>
    <div class="muted">{{ $servis->nomor_servis }} · {{ $pengambilan->waktu?->format('d/m/Y H:i') }}</div>

    <table>
        <tr><th>Pelanggan</th><td>{{ $servis->pelanggan?->nama }} · {{ $servis->pelanggan?->no_hp }}</td></tr>
        <tr><th>Perangkat</th><td>
            {{ \App\Models\Servis::JENIS_PERANGKAT[$servis->jenis_perangkat] ?? $servis->jenis_perangkat }} —
            {{ $servis->namaPerangkat() }}
        </td></tr>
        <tr><th>Nomor seri</th><td>{{ $servis->nomor_seri ?: '—' }}</td></tr>
        <tr><th>Diterima oleh</th><td>{{ $pengambilan->nama_penerima }} ({{ $pengambilan->labelVerifikasi() }})</td></tr>
        <tr><th>Diserahkan oleh</th><td>{{ $pengambilan->penyerah?->nama ?? '—' }}</td></tr>
        @if ($pengambilan->catatan)
            <tr><th>Catatan</th><td>{{ $pengambilan->catatan }}</td></tr>
        @endif
    </table>

    @php($estimasi = $servis->estimasiDisetujui())
    <table>
        <tr><th>Total tagihan</th><td class="kanan">Rp {{ number_format($servis->totalTagihan(), 0, ',', '.') }}</td></tr>
        <tr><th>Total dibayar</th><td class="kanan">Rp {{ number_format($servis->totalDibayar(), 0, ',', '.') }}</td></tr>
        <tr><th>Sisa</th><td class="kanan">Rp {{ number_format(max($servis->sisaTagihan(), 0), 0, ',', '.') }} (LUNAS)</td></tr>
    </table>

    <div class="box muted">
        Barang telah diterima dalam keadaan baik dan telah diperiksa oleh penerima.
        Bukti ini sah tanpa tanda tangan basah. Garansi servis mengikuti ketentuan {{ $bisnis['nama'] }}.
    </div>

    <table class="ttd">
        <tr>
            <td style="width:50%">
                <div class="garis">Diserahkan oleh</div>
                <div class="muted">{{ $pengambilan->penyerah?->nama ?? '—' }}</div>
            </td>
            <td style="width:50%">
                <div class="garis">Diterima oleh</div>
                <div class="muted">{{ $pengambilan->nama_penerima }}</div>
            </td>
        </tr>
    </table>
</body>
</html>