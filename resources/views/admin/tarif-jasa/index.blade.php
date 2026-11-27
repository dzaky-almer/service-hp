<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tarif Jasa</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Tarif jasa baru</h3>

                <form method="POST" action="{{ route('admin.tarif-jasa.store') }}" class="grid gap-4 sm:grid-cols-3">
                    @csrf

                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama jasa" />
                        <x-text-input id="nama" name="nama" type="text" class="mt-1 block w-full" :value="old('nama')" required />
                        <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="harga" value="Harga" />
                        <x-text-input id="harga" name="harga" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('harga')" required />
                        <x-input-error :messages="$errors->get('harga')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-3">
                        <x-primary-button>Simpan tarif</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Daftar tarif</h3>

                <table class="w-full text-sm text-left">
                    <thead class="text-gray-500 border-b">
                        <tr>
                            <th class="py-2">Nama</th>
                            <th class="py-2 text-right">Harga</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftar as $tarif)
                            <tr class="border-b align-top">
                                <td class="py-2">{{ $tarif->nama }}</td>
                                <td class="py-2 text-right">Rp{{ number_format((float) $tarif->harga, 0, ',', '.') }}</td>
                                <td class="py-2">{{ $tarif->labelStatus() }}</td>
                                <td class="py-2">
                                    <details>
                                        <summary class="cursor-pointer text-indigo-600">Ubah</summary>

                                        <form method="POST" action="{{ route('admin.tarif-jasa.update', $tarif) }}"
                                              class="grid gap-3 mt-3 min-w-[20rem]">
                                            @csrf
                                            @method('PUT')

                                            <div>
                                                <x-input-label for="nama-{{ $tarif->id_tarif }}" value="Nama jasa" />
                                                <x-text-input id="nama-{{ $tarif->id_tarif }}" name="nama" type="text"
                                                              class="mt-1 block w-full" :value="old('nama', $tarif->nama)" required />
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <x-input-label for="harga-{{ $tarif->id_tarif }}" value="Harga" />
                                                    <x-text-input id="harga-{{ $tarif->id_tarif }}" name="harga" type="number" step="0.01" min="0"
                                                                  class="mt-1 block w-full" :value="old('harga', $tarif->harga)" required />
                                                </div>

                                                <div>
                                                    <x-input-label for="status-{{ $tarif->id_tarif }}" value="Status" />
                                                    <select id="status-{{ $tarif->id_tarif }}" name="status"
                                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                                        @foreach (\App\Models\TarifJasa::LABEL_STATUS as $nilai => $label)
                                                            <option value="{{ $nilai }}" @selected(old('status', $tarif->status) === $nilai)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div>
                                                <x-primary-button>Simpan</x-primary-button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('admin.tarif-jasa.destroy', $tarif) }}"
                                              class="mt-3"
                                              onsubmit="return confirm('Hapus tarif jasa ini? Tarif yang sudah dipakai tidak bisa dihapus.')">
                                            @csrf
                                            @method('DELETE')
                                            <x-danger-button>Hapus</x-danger-button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-gray-500">Belum ada tarif jasa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>