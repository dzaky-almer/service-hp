{{-- Dashboard customer (fitur 46): servis milik pengguna sendiri. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="bg-white shadow sm:rounded-lg p-4">
                    <dt class="text-sm text-gray-500">Servis aktif</dt>
                    <dd class="text-3xl font-semibold">{{ $aktif }}</dd>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-4">
                    <dt class="text-sm text-gray-500">Sisa tagihan</dt>
                    <dd class="text-2xl font-semibold">{{ rupiah($sisaTagihan) }}</dd>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Nomor</th>
                            <th class="px-4 py-3">Perangkat</th>
                            <th class="px-4 py-3">Masuk</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($daftar as $servis)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono">
                                    <a href="{{ route('pelanggan.servis.show', $servis) }}" class="text-blue-700 hover:underline">{{ $servis->nomor_servis }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $servis->namaPerangkat() }}</td>
                                <td class="px-4 py-3">{{ $servis->tanggal_masuk?->format('d M Y') }}</td>
                                <td class="px-4 py-3">@include('servis._status')</td>
                                <td class="px-4 py-3 text-right font-mono">{{ rupiah($servis->sisaTagihan()) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada servis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>