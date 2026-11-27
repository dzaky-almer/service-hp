<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Spare Part</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            {{-- Fitur 35: tambah spare part; stok awal dicatat sebagai mutasi masuk. --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800">Spare part baru</h3>
                <p class="text-sm text-gray-500 mt-1 mb-4">
                    Stok tidak disimpan sebagai kolom: nilainya jumlah seluruh mutasi stok. Stok awal di bawah
                    dicatat sebagai mutasi masuk pertama.
                </p>

                <form method="POST" action="{{ route('admin.sparepart.store') }}" class="grid gap-4 sm:grid-cols-3">
                    @csrf

                    <div>
                        <x-input-label for="sku" value="SKU" />
                        <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full" :value="old('sku')" required />
                        <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama" />
                        <x-text-input id="nama" name="nama" type="text" class="mt-1 block w-full" :value="old('nama')" required />
                        <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="harga_beli" value="Harga beli" />
                        <x-text-input id="harga_beli" name="harga_beli" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('harga_beli', 0)" required />
                        <x-input-error :messages="$errors->get('harga_beli')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="harga_jual" value="Harga jual" />
                        <x-text-input id="harga_jual" name="harga_jual" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('harga_jual')" required />
                        <x-input-error :messages="$errors->get('harga_jual')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="stok_minimum" value="Stok minimum" />
                        <x-text-input id="stok_minimum" name="stok_minimum" type="number" min="0" class="mt-1 block w-full" :value="old('stok_minimum', 0)" />
                        <x-input-error :messages="$errors->get('stok_minimum')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="stok_awal" value="Stok awal" />
                        <x-text-input id="stok_awal" name="stok_awal" type="number" min="0" class="mt-1 block w-full" :value="old('stok_awal', 0)" />
                        <x-input-error :messages="$errors->get('stok_awal')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-3">
                        <x-primary-button>Simpan spare part</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Fitur 36: daftar spare part + stok turunan + pencarian. --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="GET" action="{{ route('admin.sparepart.index') }}" class="flex gap-2 mb-4">
                    <x-text-input name="cari" type="text" class="block w-full" :value="$cari" placeholder="Cari SKU atau nama" />
                    <x-secondary-button type="submit">Cari</x-secondary-button>
                    @if ($cari !== '')
                        <a href="{{ route('admin.sparepart.index') }}" class="inline-flex items-center text-sm text-gray-600 px-2">Reset</a>
                    @endif
                </form>

                <table class="w-full text-sm text-left">
                    <thead class="text-gray-500 border-b">
                        <tr>
                            <th class="py-2">SKU</th>
                            <th class="py-2">Nama</th>
                            <th class="py-2 text-right">Harga beli</th>
                            <th class="py-2 text-right">Harga jual</th>
                            <th class="py-2 text-right">Stok</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftar as $item)
                            @php($stok = (int) ($item->stok ?? 0))
                            <tr class="border-b align-top">
                                <td class="py-2 font-mono text-xs">{{ $item->sku }}</td>
                                <td class="py-2">
                                    <a href="{{ route('admin.sparepart.show', $item) }}" class="text-indigo-600">{{ $item->nama }}</a>
                                </td>
                                <td class="py-2 text-right">Rp{{ number_format((float) $item->harga_beli, 0, ',', '.') }}</td>
                                <td class="py-2 text-right">Rp{{ number_format((float) $item->harga_jual, 0, ',', '.') }}</td>
                                <td class="py-2 text-right {{ $stok <= $item->stok_minimum ? 'text-red-600 font-semibold' : '' }}">
                                    {{ $stok }}
                                </td>
                                <td class="py-2">{{ $item->labelStatus() }}</td>
                                <td class="py-2">
                                    <details>
                                        <summary class="cursor-pointer text-indigo-600">Ubah</summary>

                                        <form method="POST" action="{{ route('admin.sparepart.update', $item) }}"
                                              class="grid gap-3 mt-3 min-w-[22rem]">
                                            @csrf
                                            @method('PUT')

                                            <div>
                                                <x-input-label for="sku-{{ $item->id_sparepart }}" value="SKU" />
                                                <x-text-input id="sku-{{ $item->id_sparepart }}" name="sku" type="text"
                                                              class="mt-1 block w-full" :value="old('sku', $item->sku)" required />
                                            </div>

                                            <div>
                                                <x-input-label for="nama-{{ $item->id_sparepart }}" value="Nama" />
                                                <x-text-input id="nama-{{ $item->id_sparepart }}" name="nama" type="text"
                                                              class="mt-1 block w-full" :value="old('nama', $item->nama)" required />
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <x-input-label for="harga_beli-{{ $item->id_sparepart }}" value="Harga beli" />
                                                    <x-text-input id="harga_beli-{{ $item->id_sparepart }}" name="harga_beli" type="number" step="0.01" min="0"
                                                                  class="mt-1 block w-full" :value="old('harga_beli', $item->harga_beli)" required />
                                                </div>

                                                <div>
                                                    <x-input-label for="harga_jual-{{ $item->id_sparepart }}" value="Harga jual" />
                                                    <x-text-input id="harga_jual-{{ $item->id_sparepart }}" name="harga_jual" type="number" step="0.01" min="0"
                                                                  class="mt-1 block w-full" :value="old('harga_jual', $item->harga_jual)" required />
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <x-input-label for="stok_minimum-{{ $item->id_sparepart }}" value="Stok minimum" />
                                                    <x-text-input id="stok_minimum-{{ $item->id_sparepart }}" name="stok_minimum" type="number" min="0"
                                                                  class="mt-1 block w-full" :value="old('stok_minimum', $item->stok_minimum)" />
                                                </div>

                                                <div>
                                                    <x-input-label for="status-{{ $item->id_sparepart }}" value="Status" />
                                                    <select id="status-{{ $item->id_sparepart }}" name="status"
                                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                                        @foreach (\App\Models\Sparepart::LABEL_STATUS as $nilai => $label)
                                                            <option value="{{ $nilai }}" @selected(old('status', $item->status) === $nilai)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div>
                                                <x-primary-button>Simpan</x-primary-button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('admin.sparepart.destroy', $item) }}"
                                              class="mt-3"
                                              onsubmit="return confirm('Hapus spare part ini? Spare part yang sudah pernah dipakai tidak bisa dihapus.')">
                                            @csrf
                                            @method('DELETE')
                                            <x-danger-button>Hapus</x-danger-button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-gray-500">Tidak ada spare part yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $daftar->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>