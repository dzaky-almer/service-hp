<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight font-mono">{{ $servis->nomor_servis }}</h2>
                <p class="text-sm text-gray-500">Kode tracking: <span class="font-mono">{{ $servis->kode_tracking }}</span></p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.servis.tanda-terima', $servis) }}" target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-xs font-semibold uppercase rounded-md hover:bg-gray-50">
                    Tanda terima PDF
                </a>
                <a href="{{ route('admin.servis.index') }}" class="text-sm text-gray-600 hover:underline self-center">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 bg-white shadow sm:rounded-lg p-6">
                    @include('servis._ringkasan')
                </div>

                <div class="space-y-6">
                    {{-- Fitur 20: assign/pindah teknisi. --}}
                    @can('kelola', $servis)
                        <div class="bg-white shadow sm:rounded-lg p-6">
                            <h3 class="font-semibold text-gray-800 mb-3">Teknisi</h3>
                            <form method="POST" action="{{ route('admin.servis.teknisi', $servis) }}" class="space-y-3">
                                @csrf
                                <select name="id_teknisi" class="block w-full border-gray-300 rounded-md text-sm" required>
                                    <option value="">— pilih teknisi aktif —</option>
                                    @foreach ($daftarTeknisi as $teknisi)
                                        <option value="{{ $teknisi->id_pengguna }}" @selected((int) old('id_teknisi', $servis->id_teknisi) === $teknisi->id_pengguna)>
                                            {{ $teknisi->nama }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('id_teknisi')" />
                                @if ($daftarTeknisi->isEmpty())
                                    <p class="text-sm text-amber-700">Belum ada akun teknisi aktif.</p>
                                @endif
                                <x-primary-button>Tugaskan</x-primary-button>
                            </form>
                        </div>
                    @endcan

                    {{-- Fitur 22: pembatalan dengan alasan; batas peran diatur ServisService. --}}
                    @can('kelola', $servis)
                        @unless (in_array($servis->status, \App\Models\Servis::STATUS_AKHIR, true))
                            <div class="bg-white shadow sm:rounded-lg p-6">
                                <h3 class="font-semibold text-gray-800 mb-3">Batalkan servis</h3>
                                <form method="POST" action="{{ route('admin.servis.batal', $servis) }}" class="space-y-3"
                                    onsubmit="return confirm('Batalkan servis ini? Tindakan tidak dapat dibatalkan.')">
                                    @csrf
                                    <textarea name="alasan" rows="2" class="block w-full border-gray-300 rounded-md text-sm"
                                        placeholder="Alasan pembatalan" required>{{ old('alasan') }}</textarea>
                                    <x-input-error :messages="$errors->get('alasan')" />
                                    <x-input-error :messages="$errors->get('status')" />
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-xs font-semibold uppercase rounded-md hover:bg-red-700">
                                        Batalkan
                                    </button>
                                </form>
                            </div>
                        @endunless
                    @endcan
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                {{-- Fitur 19 + 25: foto dan riwayat status. --}}
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Foto</h3>
                    @include('servis._foto', ['aksiUnggah' => route('admin.servis.foto', $servis)])
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Riwayat status</h3>
                    @include('servis._riwayat')
                </div>
            </div>

            {{-- Fitur 23, 24, 25, 28: diagnosis, estimasi, kirim, revisi. --}}
            @php
                $bolehIsi = $servis->bolehDiagnosis() && auth()->user()->can('diagnosis', $servis);
                $bolehKirim = $servis->bolehDiagnosis() && auth()->user()->can('kirimEstimasi', $servis);
            @endphp

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Diagnosis</h3>
                @if ($servis->status === \App\Models\Servis::DITERIMA && auth()->user()->can('ubah', $servis))
                    <form method="POST" action="{{ route('admin.servis.diagnosis.mulai', $servis) }}" class="mb-3">
                        @csrf
                        <x-primary-button>Mulai diagnosis</x-primary-button>
                    </form>
                @endif
                @include('servis._diagnosis', [
                    'aksi' => $bolehIsi ? route('admin.servis.diagnosis', $servis) : null,
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
                    'aksiSimpan' => $bolehIsi ? route('admin.servis.estimasi.simpan', $servis) : null,
                    'aksiKirim' => $bolehKirim ? fn ($e) => route('admin.servis.estimasi.kirim', [$servis, $e]) : null,
                    'aksiRevisi' => $bolehIsi ? route('admin.servis.estimasi.revisi', $servis) : null,
                    'aksiPutuskan' => null,
                ])

                {{-- Fitur 26: estimasi ditolak -> perangkat kembali ke pelanggan. --}}
                @if ($servis->status === \App\Models\Servis::DITOLAK && auth()->user()->can('kelola', $servis))
                    <form method="POST" action="{{ route('admin.servis.kembalikan', $servis) }}" class="mt-4"
                        onsubmit="return confirm('Tandai perangkat siap diambil?')">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold uppercase rounded-md hover:bg-gray-900">
                            Tandai siap diambil (estimasi ditolak)
                        </button>
                    </form>
                @endif
            </div>

            {{-- Fitur 29–34, 37: pengerjaan, catatan, spare part, QC. --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Pengerjaan</h3>
                @include('servis._pengerjaan', [
                    'aksiStatus' => auth()->user()->can('ubah', $servis)
                        && in_array($servis->status, [\App\Models\Servis::DISETUJUI, \App\Models\Servis::DIKERJAKAN], true)
                        ? route('admin.servis.status', $servis) : null,
                    'aksiCatatan' => auth()->user()->can('ubah', $servis) && $servis->bolehCatatan()
                        ? route('admin.servis.catatan', $servis) : null,
                    'aksiSparepart' => auth()->user()->can('ubah', $servis) && $servis->status === \App\Models\Servis::DIKERJAKAN
                        ? route('admin.servis.pakai-sparepart', $servis) : null,
                    'aksiQc' => auth()->user()->can('ubah', $servis) && $servis->status === \App\Models\Servis::QUALITY_CONTROL
                        ? route('admin.servis.qc', $servis) : null,
                    'aksiSiapDiambil' => auth()->user()->can('ubah', $servis)
                        && $servis->status === \App\Models\Servis::QUALITY_CONTROL && $servis->qcLulusTerakhir()
                        ? route('admin.servis.siap-diambil', $servis) : null,
                ])
            </div>

            {{-- Fitur 18: ubah informasi perangkat/keluhan. --}}
            @can('ubah', $servis)
                <details class="bg-white shadow sm:rounded-lg p-6">
                    <summary class="font-semibold text-gray-800 cursor-pointer">Ubah informasi servis</summary>
                    <div class="mt-4">
                        @include('admin.servis._form', [
                            'aksi' => route('admin.servis.update', $servis),
                            'metode' => 'PUT',
                            'servis' => $servis,
                            'tombol' => 'Simpan perubahan',
                            'daftarPelanggan' => collect(),
                        ])
                    </div>
                </details>
            @endcan
        </div>
    </div>
</x-app-layout>