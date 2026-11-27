{{-- Dashboard admin/owner (fitur 46, 54): ringkasan, lewat SLA, servis terbaru. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('admin.servis.index') }}" class="bg-white shadow sm:rounded-lg p-4 hover:bg-gray-50">
                    <dt class="text-sm text-gray-500">Servis aktif</dt>
                    <dd class="text-3xl font-semibold">{{ $ringkasan['aktif'] }}</dd>
                </a>
                <a href="{{ route('admin.servis.terlambat') }}" class="bg-white shadow sm:rounded-lg p-4 hover:bg-gray-50">
                    <dt class="text-sm text-gray-500">Lewat SLA</dt>
                    <dd class="text-3xl font-semibold {{ $ringkasan['terlambat'] > 0 ? 'text-red-700' : '' }}">
                        {{ $ringkasan['terlambat'] }}</dd>
                </a>
                <div class="bg-white shadow sm:rounded-lg p-4">
                    <dt class="text-sm text-gray-500">Pendapatan hari ini</dt>
                    <dd class="text-2xl font-semibold">{{ rupiah($ringkasan['pendapatan_hari_ini']) }}</dd>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-4">
                    <dt class="text-sm text-gray-500">Pendapatan bulan ini</dt>
                    <dd class="text-2xl font-semibold">{{ rupiah($ringkasan['pendapatan_bulan_ini']) }}</dd>
                    @if ($batasHariIni)
                        <p class="text-xs text-gray-500">Angka penuh untuk Owner.</p>
                    @endif
                </div>
            </div>

            @if ($pembayaranMenunggu > 0)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded">
                    {{ $pembayaranMenunggu }} pembayaran menunggu konfirmasi.
                </div>
            @endif

            <section class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <h3 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b">Lewat SLA</h3>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-2">Nomor</th>
                            <th class="px-4 py-2">Pelanggan</th>
                            <th class="px-4 py-2">Teknisi</th>
                            <th class="px-4 py-2">Masuk</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($terlambat as $servis)
                            <tr>
                                <td class="px-4 py-2 font-mono">
                                    <a href="{{ route('admin.servis.show', $servis) }}" class="text-blue-700 hover:underline">{{ $servis->nomor_servis }}</a>
                                </td>
                                <td class="px-4 py-2">{{ $servis->pelanggan?->nama }}</td>
                                <td class="px-4 py-2">{{ $servis->teknisi?->nama ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $servis->tanggal_masuk?->format('d M Y') }}</td>
                                <td class="px-4 py-2">{{ $servis->labelStatus() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Tidak ada servis lewat SLA.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <h3 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b">Servis terbaru</h3>
                <table class="min-w-full text-sm">
                    <tbody class="divide-y">
                        @forelse ($terbaru as $servis)
                            <tr>
                                <td class="px-4 py-2 font-mono">
                                    <a href="{{ route('admin.servis.show', $servis) }}" class="text-blue-700 hover:underline">{{ $servis->nomor_servis }}</a>
                                </td>
                                <td class="px-4 py-2">{{ $servis->pelanggan?->nama }}</td>
                                <td class="px-4 py-2 text-right text-gray-500">{{ $servis->labelStatus() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Belum ada servis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>