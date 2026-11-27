{{-- Form data perangkat. $servis null = buat baru. --}}
<form method="POST" action="{{ $aksi }}" class="grid gap-4 sm:grid-cols-2">
    @csrf
    @if (($metode ?? 'POST') !== 'POST')
        @method($metode)
    @endif

    @if (! $servis)
        <div class="sm:col-span-2">
            <x-input-label for="id_pelanggan" value="Pelanggan" />
            <select id="id_pelanggan" name="id_pelanggan" class="mt-1 block w-full border-gray-300 rounded-md text-sm" required>
                <option value="">— pilih pelanggan aktif —</option>
                @foreach ($daftarPelanggan as $pelanggan)
                    <option value="{{ $pelanggan->id_pelanggan }}" @selected((string) old('id_pelanggan') === (string) $pelanggan->id_pelanggan)>
                        {{ $pelanggan->nama }} · {{ $pelanggan->no_hp }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('id_pelanggan')" class="mt-2" />
            @if ($daftarPelanggan->isEmpty())
                <p class="text-sm text-amber-700 mt-2">
                    Belum ada pelanggan aktif. <a href="{{ route('admin.pelanggan.index') }}" class="underline">Tambah pelanggan dulu</a>.
                </p>
            @endif
        </div>
    @endif

    <div>
        <x-input-label for="jenis_perangkat" value="Jenis perangkat" />
        <select id="jenis_perangkat" name="jenis_perangkat" class="mt-1 block w-full border-gray-300 rounded-md text-sm" required>
            @foreach (\App\Models\Servis::JENIS_PERANGKAT as $nilai => $label)
                <option value="{{ $nilai }}" @selected(old('jenis_perangkat', $servis?->jenis_perangkat) === $nilai)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('jenis_perangkat')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="merek" value="Merek" />
        <x-text-input id="merek" name="merek" type="text" class="mt-1 block w-full" :value="old('merek', $servis?->merek)" required />
        <x-input-error :messages="$errors->get('merek')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="model" value="Model" />
        <x-text-input id="model" name="model" type="text" class="mt-1 block w-full" :value="old('model', $servis?->model)" required />
        <x-input-error :messages="$errors->get('model')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="nomor_seri" value="Nomor seri (opsional)" />
        <x-text-input id="nomor_seri" name="nomor_seri" type="text" class="mt-1 block w-full" :value="old('nomor_seri', $servis?->nomor_seri)" />
        <x-input-error :messages="$errors->get('nomor_seri')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="keluhan" value="Keluhan" />
        <textarea id="keluhan" name="keluhan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md text-sm" required>{{ old('keluhan', $servis?->keluhan) }}</textarea>
        <x-input-error :messages="$errors->get('keluhan')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="kondisi_fisik" value="Kondisi fisik saat diterima (opsional)" />
        <textarea id="kondisi_fisik" name="kondisi_fisik" rows="2" class="mt-1 block w-full border-gray-300 rounded-md text-sm">{{ old('kondisi_fisik', $servis?->kondisi_fisik) }}</textarea>
        <x-input-error :messages="$errors->get('kondisi_fisik')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="kelengkapan" value="Kelengkapan (opsional)" />
        <x-text-input id="kelengkapan" name="kelengkapan" type="text" class="mt-1 block w-full" :value="old('kelengkapan', $servis?->kelengkapan)"
            placeholder="mis. charger, pouch, SIM tray" />
        <x-input-error :messages="$errors->get('kelengkapan')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-primary-button>{{ $tombol }}</x-primary-button>
    </div>
</form>