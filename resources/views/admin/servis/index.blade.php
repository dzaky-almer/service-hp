<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ ($terlambat ?? false) ? 'Servis lewat SLA' : 'Servis' }}</h2>
            <a href="{{ route('admin.servis.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold uppercase rounded-md hover:bg-gray-700">
                Servis baru
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @unless ($terlambat ?? false)
            {{-- Fitur 16: cari nomor servis, kode tracking, nama/no. HP pelanggan, merek/model. --}}
            <form method="GET" action="{{ route('admin.servis.index') }}" class="bg-white shadow sm:rounded-lg p-4 flex flex-wrap items-end gap-3">
                <div class="grow">
                    <x-input-label for="cari" value="Cari" />
                    <x-text-input id="cari" name="cari" type="text" class="mt-1 block w-full" :value="$cari"
                        placeholder="Nomor servis, kode tracking, nama, no. HP, merek, model" />
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 border-gray-300 rounded-md text-sm">
                        <option value="">Semua status</option>
                        @foreach (\App\Models\Servis::LABEL_STATUS as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($status === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Cari</x-primary-button>
            </form>
            @endunless

            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Nomor</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">Perangkat</th>
                            <th class="px-4 py-3">Teknisi</th>
                            <th class="px-4 py-3">Masuk</th>
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
                                <td class="px-4 py-3">{{ $servis->tanggal_masuk?->format('d M Y') }}</td>
                                <td class="px-4 py-3">@include('servis._status')</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Belum ada servis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $daftar->links() }}
        </div>
    </div>
</x-app-layout>