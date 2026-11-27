{{-- Foto masuk/hasil + unggah baru. $aksiUnggah = route POST, jenis default foto masuk. --}}
<div class="grid gap-4 sm:grid-cols-2">
    @forelse ($servis->foto as $foto)
        <figure class="border rounded p-2">
            <a href="{{ route('foto.show', $foto) }}" target="_blank">
                <img src="{{ route('foto.show', $foto) }}" alt="{{ $foto->labelJenis() }}" class="w-full h-40 object-cover rounded">
            </a>
            <figcaption class="text-xs text-gray-500 mt-2">
                {{ $foto->labelJenis() }} · {{ $foto->waktu?->format('d M Y H:i') }}
                @if ($foto->diunggahOleh) · {{ $foto->diunggahOleh->nama }} @endif
            </figcaption>
        </figure>
    @empty
        <p class="text-sm text-gray-500">Belum ada foto.</p>
    @endforelse
</div>

@isset($aksiUnggah)
    @can('lihat', $servis)
        <form method="POST" action="{{ $aksiUnggah }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <x-input-label for="foto" value="Unggah foto (JPG/PNG/WEBP, maks 5 MB)" />
                <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block text-sm" required>
                <x-input-error :messages="$errors->get('foto')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="jenis" value="Jenis" />
                <select id="jenis" name="jenis" class="mt-1 border-gray-300 rounded-md text-sm">
                    @foreach (\App\Models\FotoServis::LABEL_JENIS as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('jenis') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button>Unggah</x-primary-button>
        </form>
    @endcan
@endisset