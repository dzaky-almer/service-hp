{{-- Ringkasan perangkat + pelanggan; dipakai detail admin/pelanggan/teknisi. --}}
<dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 text-sm">
    <div>
        <dt class="text-gray-500">Nomor servis</dt>
        <dd class="font-mono font-semibold text-gray-900">{{ $servis->nomor_servis }}</dd>
    </div>
    <div>
        <dt class="text-gray-500">Status</dt>
        <dd class="mt-1">@include('servis._status')</dd>
    </div>
    <div>
        <dt class="text-gray-500">Pelanggan</dt>
        <dd class="text-gray-900">{{ $servis->pelanggan?->nama ?? '—' }} · {{ $servis->pelanggan?->no_hp }}</dd>
    </div>
    <div>
        <dt class="text-gray-500">Teknisi</dt>
        <dd class="text-gray-900">{{ $servis->teknisi?->nama ?? 'Belum ditugaskan' }}</dd>
    </div>
    <div>
        <dt class="text-gray-500">Perangkat</dt>
        <dd class="text-gray-900">
            {{ \App\Models\Servis::JENIS_PERANGKAT[$servis->jenis_perangkat] ?? $servis->jenis_perangkat }} ·
            {{ $servis->namaPerangkat() }}
            @if ($servis->nomor_seri)
                <span class="text-gray-500">(SN {{ $servis->nomor_seri }})</span>
            @endif
        </dd>
    </div>
    <div>
        <dt class="text-gray-500">Tanggal masuk</dt>
        <dd class="text-gray-900">{{ $servis->tanggal_masuk?->format('d M Y H:i') }}</dd>
    </div>
    <div>
        <dt class="text-gray-500">SLA</dt>
        <dd class="text-gray-900">{{ $servis->sla_hari_salinan }} hari</dd>
    </div>
    <div>
        <dt class="text-gray-500">Biaya pemeriksaan</dt>
        <dd class="text-gray-900">Rp {{ number_format((float) $servis->biaya_pemeriksaan_salinan, 0, ',', '.') }}</dd>
    </div>
    <div class="sm:col-span-2">
        <dt class="text-gray-500">Keluhan</dt>
        <dd class="text-gray-900 whitespace-pre-line">{{ $servis->keluhan }}</dd>
    </div>
    @if ($servis->kondisi_fisik)
        <div class="sm:col-span-2">
            <dt class="text-gray-500">Kondisi fisik saat diterima</dt>
            <dd class="text-gray-900 whitespace-pre-line">{{ $servis->kondisi_fisik }}</dd>
        </div>
    @endif
    @if ($servis->kelengkapan)
        <div class="sm:col-span-2">
            <dt class="text-gray-500">Kelengkapan</dt>
            <dd class="text-gray-900">{{ $servis->kelengkapan }}</dd>
        </div>
    @endif
</dl>