{{-- Laporan servis per status + durasi penyelesaian (fitur 47, 50). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Laporan servis</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('laporan._filter', ['action' => request()->routeIs('teknisi.*')
                ? route('teknisi.laporan.servis') : route('admin.laporan.servis')])

            <div class="grid gap-6 sm:grid-cols-3">
                <div class="bg-white shadow sm:rounded-lg p-4">
                    <dt class="text-sm text-gray-500">Total servis</dt>
                    <dd class="text-2xl font-semibold">{{ array_sum($perStatus) }}</dd>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-4">
                    <dt class="text-sm text-gray-500">Lewat SLA</dt>
                    <dd class="text-2xl font-semibold {{ $terlambat > 0 ? 'text-red-700' : '' }}">{{ $terlambat }}</dd>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-4">
                    <dt class="text-sm text-gray-500">Rata-rata penyelesaian</dt>
                    <dd class="text-2xl font-semibold">
                        {{ $durasi['rata_jam'] === null ? '—' : $durasi['rata_jam'].' jam' }}
                    </dd>
                    <p class="text-xs text-gray-500">{{ $durasi['jumlah'] }} servis selesai</p>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($perStatus as $status => $jumlah)
                            <tr class="{{ $jumlah === 0 ? 'text-gray-400' : '' }}">
                                <td class="px-4 py-2">{{ \App\Models\Servis::LABEL_STATUS[$status] ?? $status }}</td>
                                <td class="px-4 py-2 text-right">{{ $jumlah }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>