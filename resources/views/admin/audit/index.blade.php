{{-- Riwayat perubahan (fitur 53). Hanya baca. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat perubahan</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('admin.audit.index') }}" class="bg-white shadow sm:rounded-lg p-4 flex flex-wrap items-end gap-3">
                <div class="grow">
                    <x-input-label for="cari" value="Cari pelaku / objek" />
                    <x-text-input id="cari" name="cari" type="text" class="mt-1 block w-full" :value="$cari" />
                </div>
                <div>
                    <x-input-label for="aksi" value="Aksi" />
                    <select id="aksi" name="aksi" class="mt-1 border-gray-300 rounded-md text-sm">
                        <option value="">Semua aksi</option>
                        @foreach ($daftarAksi as $satu)
                            <option value="{{ $satu }}" @selected($aksi === $satu)>{{ $satu }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Cari</x-primary-button>
            </form>

            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Pelaku</th>
                            <th class="px-4 py-3">Aksi</th>
                            <th class="px-4 py-3">Objek</th>
                            <th class="px-4 py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($daftar as $baris)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $baris->waktu }}</td>
                                <td class="px-4 py-2">{{ $baris->pelaku ?? 'sistem' }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $baris->aksi }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $baris->objek_tipe }}#{{ $baris->objek_id }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $baris->ip ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada riwayat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $daftar->links() }}
        </div>
    </div>
</x-app-layout>