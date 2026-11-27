<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $sparepart->nama }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">SKU</dt>
                        <dd class="font-mono">{{ $sparepart->sku }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Harga jual</dt>
                        <dd>Rp{{ number_format((float) $sparepart->harga_jual, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Stok sekarang</dt>
                        <dd class="font-semibold {{ $stok <= $sparepart->stok_minimum ? 'text-red-600' : '' }}">{{ $stok }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd>{{ $sparepart->labelStatus() }}</dd>
                    </div>
                </dl>

                <p class="mt-4 text-sm text-gray-500">
                    Stok = jumlah semua mutasi (naik lewat stok masuk, turun lewat pemakaian servis).
                </p>

                <a href="{{ route('admin.sparepart.index') }}" class="inline-block mt-4 text-sm text-indigo-600">Kembali ke daftar</a>
            </div>

            {{-- Fitur 38: penyesuaian stok manual (alasan wajib). --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Penyesuaian stok</h3>
                <form method="POST" action="{{ route('admin.sparepart.penyesuaian', $sparepart) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="w-32">
                        <x-input-label for="jumlah" value="Jumlah (+/-)" />
                        <input id="jumlah" name="jumlah" type="number" step="1" value="{{ old('jumlah') }}" required
                            class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                        <x-input-error :messages="$errors->get('jumlah')" />
                    </div>
                    <div class="grow">
                        <x-input-label for="alasan" value="Alasan" />
                        <x-text-input id="alasan" name="alasan" type="text" class="mt-1 block w-full" :value="old('alasan')"
                            placeholder="mis. hasil stock opname" required />
                        <x-input-error :messages="$errors->get('alasan')" />
                    </div>
                    <x-primary-button>Simpan penyesuaian</x-primary-button>
                </form>
                <p class="mt-2 text-xs text-gray-500">Jumlah negatif mengurangi stok. Stok tidak boleh negatif.</p>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Riwayat mutasi stok</h3>

                <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-gray-500 border-b">
                        <tr>
                            <th class="py-2">Waktu</th>
                            <th class="py-2">Jenis</th>
                            <th class="py-2 text-right">Jumlah</th>
                            <th class="py-2">Alasan</th>
                            <th class="py-2">Dicatat oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mutasi as $baris)
                            <tr class="border-b">
                                <td class="py-2">{{ $baris->waktu?->format('d/m/Y H:i') }}</td>
                                <td class="py-2">{{ $baris->labelJenis() }}</td>
                                <td class="py-2 text-right {{ $baris->jumlah < 0 ? 'text-red-600' : '' }}">
                                    {{ $baris->jumlah > 0 ? '+' : '' }}{{ $baris->jumlah }}
                                </td>
                                <td class="py-2">{{ $baris->alasan ?? '-' }}</td>
                                <td class="py-2">{{ $baris->dicatatOleh?->nama ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-gray-500">Belum ada mutasi stok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>