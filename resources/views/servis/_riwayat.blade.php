{{-- Riwayat status (fitur 25): hanya tambah, tidak pernah diubah. --}}
<ol class="space-y-3 text-sm">
    @foreach ($servis->riwayat as $baris)
        <li class="border-l-2 border-gray-200 pl-4">
            <p class="font-medium text-gray-900">
                {{ $baris->labelDari() }} &rarr; {{ $baris->labelKe() }}
            </p>
            <p class="text-gray-500">
                {{ $baris->waktu?->format('d M Y H:i') }}
                @if ($baris->diubahOleh)
                    · {{ $baris->diubahOleh->nama }}
                @endif
            </p>
            @if ($baris->alasan)
                <p class="text-gray-700 mt-1">{{ $baris->alasan }}</p>
            @endif
        </li>
    @endforeach
</ol>