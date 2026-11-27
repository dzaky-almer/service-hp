{{-- Pendapatan bersih per bulan/hari (fitur 48). $batasHariIni = admin hanya hari ini. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Laporan pendapatan</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('laporan._filter', ['action' => route('admin.laporan.pendapatan')])

            @if ($batasHariIni)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded">
                    Admin hanya melihat pendapatan hari ini. Rentang lain khusus Owner.
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3">{{ $batasHariIni ? 'Tanggal' : 'Bulan' }}</th>
                            <th class="px-4 py-3 text-right">Pendapatan bersih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($baris as $row)
                            <tr>
                                <td class="px-4 py-2">{{ $row['periode'] }}</td>
                                <td class="px-4 py-2 text-right font-mono">{{ rupiah($row['total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-4 py-6 text-center text-gray-500">Belum ada pembayaran berhasil.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($baris)
                        <tfoot class="bg-gray-50 font-semibold">
                            <tr>
                                <td class="px-4 py-2">Total</td>
                                <td class="px-4 py-2 text-right font-mono">{{ rupiah(array_sum(array_column($baris, 'total'))) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-app-layout>