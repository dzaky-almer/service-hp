<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pekerjaan saya</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Nomor</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">Perangkat</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($daftar as $servis)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono">
                                    <a href="{{ route('teknisi.pekerjaan.show', $servis) }}" class="text-blue-700 hover:underline">{{ $servis->nomor_servis }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $servis->pelanggan?->nama }}</td>
                                <td class="px-4 py-3">{{ $servis->namaPerangkat() }}</td>
                                <td class="px-4 py-3">@include('servis._status')</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada pekerjaan yang ditugaskan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $daftar->links() }}
        </div>
    </div>
</x-app-layout>