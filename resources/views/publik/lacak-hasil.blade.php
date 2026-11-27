<x-publik-layout>
    <div class="max-w-2xl mx-auto space-y-6">
        <div class="bg-white shadow sm:rounded-lg p-8">
            {{-- BR-14: tanpa nama/no. HP pelanggan, tanpa keluhan, tanpa data biaya. --}}
            <h1 class="font-mono text-lg font-semibold text-gray-900">{{ $servis->nomor_servis }}</h1>
            <p class="mt-1">@include('servis._status')</p>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-gray-500">Perangkat</dt>
                    <dd class="text-gray-900">{{ $servis->namaPerangkat() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Tanggal masuk</dt>
                    <dd class="text-gray-900">{{ $servis->tanggal_masuk?->format('d M Y') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white shadow sm:rounded-lg p-8">
            <h2 class="font-semibold text-gray-800 mb-4">Riwayat status</h2>
            <ol class="space-y-3 text-sm">
                @foreach ($servis->riwayat as $baris)
                    <li class="border-l-2 border-gray-200 pl-4">
                        <p class="font-medium text-gray-900">{{ $baris->labelKe() }}</p>
                        <p class="text-gray-500">{{ $baris->waktu?->format('d M Y H:i') }}</p>
                    </li>
                @endforeach
            </ol>
        </div>

        <a href="{{ route('publik.lacak.index') }}" class="text-sm text-blue-700 hover:underline">Lacak nomor lain</a>
    </div>
</x-publik-layout>