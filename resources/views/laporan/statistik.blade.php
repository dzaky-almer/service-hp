{{-- Statistik spare part & kerusakan (fitur 50). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Statistik</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('laporan._filter', ['action' => request()->routeIs('teknisi.*')
                ? route('teknisi.laporan.statistik') : route('admin.laporan.statistik')])

            <section class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <h3 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b">Spare part terpakai</h3>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-2">SKU</th>
                            <th class="px-4 py-2">Nama</th>
                            <th class="px-4 py-2 text-right">Terpakai</th>
                            <th class="px-4 py-2 text-right">Biaya</th>
                            <th class="px-4 py-2 text-right">Stok</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($sparepart as $row)
                            <tr>
                                <td class="px-4 py-2 font-mono">{{ $row->sku }}</td>
                                <td class="px-4 py-2">{{ $row->nama }}</td>
                                <td class="px-4 py-2 text-right">{{ $row->terpakai }}</td>
                                <td class="px-4 py-2 text-right font-mono">{{ rupiah($row->biaya) }}</td>
                                <td class="px-4 py-2 text-right {{ $row->stok <= 0 ? 'text-red-700' : '' }}">{{ $row->stok }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada pemakaian spare part.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <h3 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b">Kategori kerusakan terbanyak</h3>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-2">Kategori</th>
                            <th class="px-4 py-2 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($kerusakan as $row)
                            <tr>
                                <td class="px-4 py-2">{{ $row['kategori'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $row['jumlah'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-4 py-6 text-center text-gray-500">Belum ada diagnosis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>