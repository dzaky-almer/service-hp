<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengaturan Bisnis</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">
                    SLA dipakai sebagai salinan saat servis dibuat; mengubahnya tidak mengubah servis yang sudah ada.
                </p>

                <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <div class="sm:col-span-2">
                        <x-input-label for="nama_bisnis" value="Nama bisnis" />
                        <x-text-input id="nama_bisnis" name="nama_bisnis" type="text" class="mt-1 block w-full"
                                      :value="old('nama_bisnis', $nilai['nama_bisnis'] ?? '')" required />
                        <x-input-error :messages="$errors->get('nama_bisnis')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="alamat_bisnis" value="Alamat" />
                        <x-text-input id="alamat_bisnis" name="alamat_bisnis" type="text" class="mt-1 block w-full"
                                      :value="old('alamat_bisnis', $nilai['alamat_bisnis'] ?? '')" />
                        <x-input-error :messages="$errors->get('alamat_bisnis')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="sla_hari" value="SLA (hari)" />
                        <x-text-input id="sla_hari" name="sla_hari" type="number" min="1" max="365" class="mt-1 block w-full"
                                      :value="old('sla_hari', $nilai['sla_hari'] ?? 7)" required />
                        <x-input-error :messages="$errors->get('sla_hari')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="biaya_pemeriksaan" value="Biaya pemeriksaan" />
                        <x-text-input id="biaya_pemeriksaan" name="biaya_pemeriksaan" type="number" step="0.01" min="0"
                                      class="mt-1 block w-full"
                                      :value="old('biaya_pemeriksaan', $nilai['biaya_pemeriksaan'] ?? 0)" required />
                        <x-input-error :messages="$errors->get('biaya_pemeriksaan')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-primary-button>Simpan pengaturan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>