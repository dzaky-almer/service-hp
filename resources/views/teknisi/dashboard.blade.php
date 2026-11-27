{{-- Dashboard teknisi (fitur 46): tugas berjalan miliknya sendiri. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="bg-white shadow sm:rounded-lg p-4">
                    <dt class="text-sm text-gray-500">Tugas saya aktif</dt>
                    <dd class="text-3xl font-semibold">{{ $tugas->count() }}</dd>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-4">
                    <dt class="text-sm text-gray-500">Lewat SLA</dt>
                    <dd class="text-3xl font-semibold {{ $terlambat->count() > 0 ? 'text-red-700' : '' }}">{{ $terlambat->count() }}</dd>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-4">
                    <dt class="text-sm text-gray-500">Selesai (total)</dt>
                    <dd class="text-3xl font-semibold">{{ $ringkasan['per_status'][\App\Models\Servis::SELESAI] ?? 0 }}</dd>
                </div>
            </div>

            <section class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <h3 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b">Tugas berjalan</h3>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-2">Nomor</th>
                            <th class="px-4 py-2">Pelanggan</th>
                            <th class="px-4 py-2">Perangkat</th>
                            <th class="px-4 py-2">Masuk</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($tugas as $servis)
                            <tr class="{{ $servis->terlambat() ? 'bg-red-50' : '' }}">
                                <td class="px-4 py-2 font-mono">
                                    <a href="{{ route('teknisi.pekerjaan.show', $servis) }}" class="text-blue-700 hover:underline">{{ $servis->nomor_servis }}</a>
                                </td>
                                <td class="px-4 py-2">{{ $servis->pelanggan?->nama }}</td>
                                <td class="px-4 py-2">{{ $servis->namaPerangkat() }}</td>
                                <td class="px-4 py-2">{{ $servis->tanggal_masuk?->format('d M Y') }}</td>
                                <td class="px-4 py-2">{{ $servis->labelStatus() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Tidak ada tugas berjalan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>