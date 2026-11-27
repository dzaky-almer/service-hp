@php
    $warna = match ($servis->status) {
        \App\Models\Servis::SELESAI => 'bg-green-100 text-green-800',
        \App\Models\Servis::SIAP_DIAMBIL => 'bg-blue-100 text-blue-800',
        \App\Models\Servis::DIBATALKAN, \App\Models\Servis::DITOLAK => 'bg-red-100 text-red-800',
        \App\Models\Servis::DIKERJAKAN, \App\Models\Servis::QUALITY_CONTROL => 'bg-amber-100 text-amber-800',
        default => 'bg-gray-100 text-gray-800',
    };
@endphp
<span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $warna }}">{{ $servis->labelStatus() }}</span>