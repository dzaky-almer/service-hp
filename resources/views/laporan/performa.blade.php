{{-- Performa teknisi (fitur 49): jumlah tugas, selesai, rata-rata jam. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Performa teknisi</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('laporan._filter', ['action' => request()->routeIs('teknisi.*')
                ? route('teknisi.laporan.performa') : route('admin.laporan.performa')])

            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Teknisi</th>
                            <th class="px-4 py-3 text-right">Ditugaskan</th>
                            <th class="px-4 py-3 text-right">Selesai</th>
                            <th class="px-4 py-3 text-right">Rata-rata jam</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($baris as $row)
                            <tr>
                                <td class="px-4 py-2">{{ $row->nama }}</td>
                                <td class="px-4 py-2 text-right">{{ $row->tugas }}</td>
                                <td class="px-4 py-2 text-right">{{ $row->selesai }}</td>
                                <td class="px-4 py-2 text-right">{{ $row->rata_jam === null ? '—' : $row->rata_jam.' jam' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada data teknisi pada rentang ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>