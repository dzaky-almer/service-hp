{{-- Fitur 44, 45: serah terima barang dan bukti pengambilan. --}}
@php
    $pengambilan = $servis->pengambilan()->with('penyerah')->first();
    $bolehSerah = $servis->status === \App\Models\Servis::SIAP_DIAMBIL && $servis->lunas() && $pengambilan === null;
@endphp

@if ($pengambilan)
    <div class="space-y-2">
        <p class="text-sm text-gray-700">
            Diserahkan <span class="font-medium">{{ $pengambilan->waktu?->format('d/m/Y H:i') }}</span>
            kepada <span class="font-medium">{{ $pengambilan->nama_penerima }}</span>
            ({{ $pengambilan->labelVerifikasi() }}) oleh {{ $pengambilan->penyerah?->nama ?? '—' }}.
        </p>
        @if ($pengambilan->catatan)
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $pengambilan->catatan }}</p>
        @endif
        <a href="{{ route('admin.servis.bukti-pengambilan', $servis) }}"
            class="inline-block text-sm font-semibold text-indigo-700 hover:underline">Unduh bukti pengambilan (PDF)</a>
    </div>
@elseif ($bolehSerah && $aksiSerah)
    <form method="POST" action="{{ $aksiSerah }}" class="space-y-3"
        onsubmit="return confirm('Serahkan barang dan tutup servis ini?')">
        @csrf
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <x-input-label for="nama_penerima" value="Nama penerima" />
                <x-text-input id="nama_penerima" name="nama_penerima" class="mt-1 block w-full"
                    :value="old('nama_penerima', $servis->pelanggan?->nama)" />
                <x-input-error :messages="$errors->get('nama_penerima')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="metode_verifikasi" value="Verifikasi" />
                <select id="metode_verifikasi" name="metode_verifikasi" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @foreach (\App\Models\Pengambilan::METODE as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('metode_verifikasi') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('metode_verifikasi')" class="mt-1" />
            </div>
        </div>
        <div>
            <x-input-label for="catatan" value="Catatan (opsional)" />
            <x-text-input id="catatan" name="catatan" class="mt-1 block w-full" :value="old('catatan')" maxlength="255" />
            <x-input-error :messages="$errors->get('catatan')" class="mt-1" />
        </div>
        <x-primary-button>Serahkan barang &amp; selesaikan servis</x-primary-button>
    </form>
@elseif ($servis->status === \App\Models\Servis::SIAP_DIAMBIL && ! $servis->lunas())
    <p class="text-sm text-red-700">
        Tagihan belum lunas (sisa {{ rupiah($servis->sisaTagihan()) }}). Serah terima baru bisa setelah lunas.
    </p>
@endif