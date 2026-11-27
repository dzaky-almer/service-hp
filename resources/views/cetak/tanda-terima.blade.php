<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tanda Terima {{ $servis->nomor_servis }}</title>
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
    </style>
</head>
<body>
    <div class="qr">
        {!! $qr !!}
        <div class="muted" style="font-size:9px">Lacak: {{ $servis->kode_tracking }}</div>
    </div>

    <h1>{{ $bisnis['nama'] }}</h1>
    <div class="muted">{{ $bisnis['alamat'] }}</div>
    <div style="margin-top:12px; font-size:13px; font-weight:bold">TANDA TERIMA SERVIS</div>
    <div class="muted">{{ $servis->nomor_servis }} · {{ $servis->tanggal_masuk?->format('d/m/Y H:i') }}</div>

    <table>
        <tr><th>Pelanggan</th><td>{{ $servis->pelanggan?->nama }} · {{ $servis->pelanggan?->no_hp }}</td></tr>
        <tr><th>Perangkat</th><td>
            {{ \App\Models\Servis::JENIS_PERANGKAT[$servis->jenis_perangkat] ?? $servis->jenis_perangkat }} —
            {{ $servis->namaPerangkat() }}
        </td></tr>
        <tr><th>Nomor seri</th><td>{{ $servis->nomor_seri ?: '—' }}</td></tr>
        <tr><th>Kelengkapan</th><td>{{ $servis->kelengkapan ?: '—' }}</td></tr>
        <tr><th>Keluhan</th><td>{{ $servis->keluhan }}</td></tr>
        <tr><th>Kondisi fisik diterima</th><td>{{ $servis->kondisi_fisik ?: '—' }}</td></tr>
        <tr><th>Target selesai (SLA)</th><td>{{ $servis->sla_hari_salinan }} hari</td></tr>
        <tr><th>Biaya pemeriksaan</th><td>Rp {{ number_format((float) $servis->biaya_pemeriksaan_salinan, 0, ',', '.') }}</td></tr>
    </table>

    <div class="box muted">
        Simpan tanda terima ini. Status pengerjaan dapat dipantau kapan saja lewat halaman Lacak
        menggunakan kode <b>{{ $servis->kode_tracking }}</b> atau memindai QR di atas.
        Barang yang tidak diambil lebih dari 30 hari setelah status Siap Diambil di luar tanggung jawab kami.
    </div>

    <table class="ttd">
        <tr>
            <td style="width:50%">
                <div class="garis">Petugas penerima</div>
                <div class="muted">{{ $servis->dibuatOleh?->nama }}</div>
            </td>
            <td style="width:50%">
                <div class="garis">Pelanggan</div>
                <div class="muted">{{ $servis->pelanggan?->nama }}</div>
            </td>
        </tr>
    </table>
</body>
</html>