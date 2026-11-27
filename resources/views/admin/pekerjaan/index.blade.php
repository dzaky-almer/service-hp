<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pekerjaan</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Fitur 29: semua pekerjaan beserta teknisinya. --}}
            <form method="GET" action="{{ route('admin.pekerjaan.index') }}" class="bg-white shadow sm:rounded-lg p-4 flex flex-wrap items-end gap-3">
                <div class="grow">
                    <x-input-label for="cari" value="Cari" />
                    <x-text-input id="cari" name="cari" type="text" class="mt-1 block w-full" :value="$cari"
                        placeholder="Nomor servis, nama pelanggan, merek, model" />
                </div>
                <x-primary-button>Cari</x-primary-button>
            </form>

            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Nomor</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">Perangkat</th>
                            <th class="px-4 py-3">Teknisi</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($daftar as $servis)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono">
                                    <a href="{{ route('admin.servis.show', $servis) }}" class="text-blue-700 hover:underline">{{ $servis->nomor_servis }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $servis->pelanggan?->nama }}<div class="text-gray-500">{{ $servis->pelanggan?->no_hp }}</div></td>
                                <td class="px-4 py-3">{{ $servis->namaPerangkat() }}</td>
                                <td class="px-4 py-3">{{ $servis->teknisi?->nama ?? '—' }}</td>
                                <td class="px-4 py-3">@include('servis._status')</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada pekerjaan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $daftar->links() }}
        </div>
    </div>
</x-app-layout>