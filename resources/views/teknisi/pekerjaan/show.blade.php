<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight font-mono">{{ $servis->nomor_servis }}</h2>
            <a href="{{ route('teknisi.pekerjaan.index') }}" class="text-sm text-gray-600 hover:underline">Kembali</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-6">
                @include('servis._ringkasan')
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Foto</h3>
                    @include('servis._foto', ['aksiUnggah' => route('teknisi.pekerjaan.foto', $servis)])
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Riwayat status</h3>
                    @include('servis._riwayat')
                </div>
            </div>

            {{-- Fitur 23, 24, 28: teknisi mencatat diagnosis dan menyusun draf estimasi. --}}
            @php
                $bolehIsi = $servis->bolehDiagnosis() && auth()->user()->can('diagnosis', $servis);
            @endphp

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Diagnosis</h3>
                @if ($servis->status === \App\Models\Servis::DITERIMA && auth()->user()->can('ubah', $servis))
                    <form method="POST" action="{{ route('teknisi.pekerjaan.diagnosis.mulai', $servis) }}" class="mb-3">
                        @csrf
                        <x-primary-button>Mulai diagnosis</x-primary-button>
                    </form>
                @endif
                @include('servis._diagnosis', [
                    'aksi' => $bolehIsi ? route('teknisi.pekerjaan.diagnosis', $servis) : null,
                ])
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <h3 class="font-semibold text-gray-800">Estimasi biaya</h3>
                    <p class="text-sm text-gray-600">
                        Total tagihan: <span class="font-mono font-semibold">{{ rupiah($servis->totalTagihan()) }}</span>
                    </p>
                </div>
                @include('servis._estimasi', [
                    'aksiSimpan' => $bolehIsi ? route('teknisi.pekerjaan.estimasi.simpan', $servis) : null,
                    'aksiKirim' => null,
                    'aksiRevisi' => $bolehIsi ? route('teknisi.pekerjaan.estimasi.revisi', $servis) : null,
                    'aksiPutuskan' => null,
                ])
                <p class="mt-3 text-xs text-gray-500">Pengiriman estimasi ke pelanggan dilakukan Admin/Owner.</p>
            </div>

            {{-- Fitur 29–34, 37: pengerjaan, catatan, spare part, QC. --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Pengerjaan</h3>
                @include('servis._pengerjaan', [
                    'aksiStatus' => auth()->user()->can('ubah', $servis)
                        && in_array($servis->status, [\App\Models\Servis::DISETUJUI, \App\Models\Servis::DIKERJAKAN], true)
                        ? route('teknisi.pekerjaan.status', $servis) : null,
                    'aksiCatatan' => auth()->user()->can('ubah', $servis) && $servis->bolehCatatan()
                        ? route('teknisi.pekerjaan.catatan', $servis) : null,
                    'aksiSparepart' => auth()->user()->can('ubah', $servis) && $servis->status === \App\Models\Servis::DIKERJAKAN
                        ? route('teknisi.pekerjaan.pakai-sparepart', $servis) : null,
                    'aksiQc' => auth()->user()->can('ubah', $servis) && $servis->status === \App\Models\Servis::QUALITY_CONTROL
                        ? route('teknisi.pekerjaan.qc', $servis) : null,
                    'aksiSiapDiambil' => auth()->user()->can('ubah', $servis)
                        && $servis->status === \App\Models\Servis::QUALITY_CONTROL && $servis->qcLulusTerakhir()
                        ? route('teknisi.pekerjaan.siap-diambil', $servis) : null,
                ])
            </div>

            <details class="bg-white shadow sm:rounded-lg p-6">
                <summary class="font-semibold text-gray-800 cursor-pointer">Ubah informasi servis</summary>
                <div class="mt-4">
                    @include('admin.servis._form', [
                        'aksi' => route('teknisi.pekerjaan.update', $servis),
                        'metode' => 'PUT',
                        'servis' => $servis,
                        'tombol' => 'Simpan perubahan',
                        'daftarPelanggan' => collect(),
                    ])
                </div>
            </details>
        </div>
    </div>
</x-app-layout>