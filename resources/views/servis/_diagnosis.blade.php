<div class="space-y-4">
    @forelse ($servis->diagnosis->sortByDesc('versi') as $diagnosis)
        <div class="border border-gray-200 rounded-md p-3">
            <div class="flex flex-wrap items-center justify-between gap-1 text-xs text-gray-500">
                <span>
                    Versi {{ $diagnosis->versi }} ·
                    <span class="font-medium text-gray-700">{{ $diagnosis->kategori_kerusakan }}</span>
                </span>
                <span>{{ $diagnosis->waktu?->format('d/m/Y H:i') }} · {{ $diagnosis->pembuat?->nama ?? '—' }}</span>
            </div>
            <p class="mt-2 text-sm text-gray-800 whitespace-pre-line">{{ $diagnosis->hasil }}</p>
        </div>
    @empty
        <p class="text-sm text-gray-500">Belum ada diagnosis.</p>
    @endforelse

    @if ($aksi)
        <form method="POST" action="{{ $aksi }}" class="space-y-3 border-t border-gray-200 pt-4">
            @csrf
            <div>
                <label for="kategori_kerusakan" class="block text-sm text-gray-700">Kategori kerusakan</label>
                <input id="kategori_kerusakan" name="kategori_kerusakan" value="{{ old('kategori_kerusakan') }}"
                    maxlength="60" required placeholder="mis. kerusakan layar"
                    class="block w-full border-gray-300 rounded-md text-sm">
                <x-input-error :messages="$errors->get('kategori_kerusakan')" />
            </div>
            <div>
                <label for="hasil" class="block text-sm text-gray-700">Hasil diagnosis</label>
                <textarea id="hasil" name="hasil" rows="3" required
                    class="block w-full border-gray-300 rounded-md text-sm">{{ old('hasil') }}</textarea>
                <x-input-error :messages="$errors->get('hasil')" />
            </div>
            <x-input-error :messages="$errors->get('diagnosis')" />
            <x-primary-button>Catat diagnosis</x-primary-button>
            <p class="text-xs text-gray-500">Koreksi diagnosis disimpan sebagai versi baru; versi lama tetap tersimpan.</p>
        </form>
    @endif
</div>