<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight font-mono">{{ $servis->nomor_servis }}</h2>
            <a href="{{ route('pelanggan.servis.index') }}" class="text-sm text-gray-600 hover:underline">Kembali</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                @include('servis._ringkasan')
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Foto</h3>
                    @include('servis._foto', ['aksiUnggah' => route('pelanggan.servis.foto', $servis)])
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Riwayat status</h3>
                    @include('servis._riwayat')
                </div>
            </div>
        {{-- Fitur 23, 27: diagnosis (baca) dan persetujuan estimasi. --}}
            @php
                $bolehPutuskan = auth()->user()->can('putuskanEstimasi', $servis);
            @endphp

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Diagnosis</h3>
                @include('servis._diagnosis', ['aksi' => null])
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <h3 class="font-semibold text-gray-800">Estimasi biaya</h3>
                    <p class="text-sm text-gray-600">
                        Total tagihan: <span class="font-mono font-semibold">{{ rupiah($servis->totalTagihan()) }}</span>
                    </p>
                </div>
                @include('servis._estimasi', [
                    'aksiSimpan' => null,
                    'aksiKirim' => null,
                    'aksiRevisi' => null,
                    'aksiPutuskan' => $bolehPutuskan ? [
                        'setujui' => fn ($e) => route('pelanggan.servis.estimasi.setujui', [$servis, $e]),
                        'tolak' => fn ($e) => route('pelanggan.servis.estimasi.tolak', [$servis, $e]),
                    ] : null,
                ])
            </div>
        </div>
    </div>
</x-app-layout>