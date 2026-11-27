@php
    $warna = [
        \App\Models\Estimasi::DRAF => 'bg-gray-100 text-gray-700',
        \App\Models\Estimasi::DIKIRIM => 'bg-blue-100 text-blue-800',
        \App\Models\Estimasi::DISETUJUI => 'bg-green-100 text-green-800',
        \App\Models\Estimasi::DITOLAK => 'bg-red-100 text-red-800',
        \App\Models\Estimasi::DIGANTIKAN => 'bg-gray-100 text-gray-500',
    ];
    $daftarEstimasi = $servis->estimasi->sortByDesc('versi');
@endphp

<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-gray-500">Harga diambil dari master tarif/spare part saat item dibuat; total dihitung server.</p>
        @if ($aksiRevisi && $servis->estimasiDisetujui())
            <form method="POST" action="{{ $aksiRevisi }}">
                @csrf
                <button type="submit"
                    class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 text-xs font-semibold uppercase rounded-md hover:bg-gray-50">
                    Buat revisi
                </button>
            </form>
        @endif
    </div>

    <x-input-error :messages="$errors->get('estimasi')" />
    <x-input-error :messages="$errors->get('items')" />

    @forelse ($daftarEstimasi as $e)
        <div class="border border-gray-200 rounded-md overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 bg-gray-50 text-sm">
                <span class="font-semibold text-gray-800">
                    Estimasi versi {{ $e->versi }}
                    <span class="ml-1 px-2 py-0.5 rounded text-xs font-medium {{ $warna[$e->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $e->labelStatus() }}</span>
                </span>
                <span class="font-mono">{{ rupiah($e->total()) }}</span>
            </div>

            <div class="p-3 space-y-3">
                @if ($e->status !== \App\Models\Estimasi::DRAF || ! $aksiSimpan)
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase text-gray-500">
                            <tr>
                                <th class="text-left pb-1">Deskripsi</th>
                                <th class="text-right pb-1">Jumlah</th>
                                <th class="text-right pb-1">Harga</th>
                                <th class="text-right pb-1">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($e->item as $item)
                                <tr class="border-t border-gray-100">
                                    <td class="py-1">
                                        {{ $item->deskripsi }}
                                        <span class="text-xs text-gray-500">({{ $item->jenis }})</span>
                                    </td>
                                    <td class="text-right py-1">{{ $item->jumlah }}</td>
                                    <td class="text-right py-1 font-mono">{{ rupiah($item->harga_satuan_salinan) }}</td>
                                    <td class="text-right py-1 font-mono">{{ rupiah($item->subtotal()) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                    <p class="text-xs text-gray-500">
                        Dibuat {{ $e->pembuat?->nama ?? '—' }} · {{ $e->dibuat_pada?->format('d/m/Y H:i') }}
                        @if ($e->dikirim_pada) · dikirim {{ $e->dikirim_pada->format('d/m/Y H:i') }} @endif
                        @if ($e->diputuskan_pada) · diputuskan {{ $e->diputuskan_pada->format('d/m/Y H:i') }} @endif
                    </p>
                    @if ($e->catatan_customer)
                        <p class="text-sm text-gray-700">Catatan pelanggan: {{ $e->catatan_customer }}</p>
                    @endif
                @endif

                {{-- Fitur 24: sunting draf. Draf diubah = item diganti seluruhnya. --}}
                @if ($e->status === \App\Models\Estimasi::DRAF && $aksiSimpan)
                    <form method="POST" action="{{ $aksiSimpan }}" class="space-y-2">
                        @csrf
                        <div data-baris-item class="space-y-2">
                            @foreach ($e->item as $i => $item)
                                @include('servis._baris-estimasi', [
                                    'i' => $i,
                                    'sumberTerpilih' => $item->id_sparepart ? 'sparepart:'.$item->id_sparepart : 'jasa:'.$item->id_tarif,
                                    'jumlah' => $item->jumlah,
                                ])
                            @endforeach
                            @if ($e->item->isEmpty())
                                @include('servis._baris-estimasi', ['i' => 0, 'sumberTerpilih' => '', 'jumlah' => 1])
                            @endif
                        </div>
                        <template>
                            @include('servis._baris-estimasi', ['i' => '__i__', 'sumberTerpilih' => '', 'jumlah' => 1])
                        </template>
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" data-tambah-item
                                class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 text-xs font-semibold uppercase rounded-md hover:bg-gray-50">
                                Tambah baris
                            </button>
                            <x-primary-button>Simpan draf</x-primary-button>
                        </div>
                    </form>
                @endif

                {{-- Fitur 25: kirim ke pelanggan (dari draf). --}}
                @if ($e->status === \App\Models\Estimasi::DRAF && $aksiKirim)
                    <form method="POST" action="{{ $aksiKirim($e) }}">
                        @csrf
                        <x-primary-button>Kirim ke pelanggan</x-primary-button>
                        <span class="text-xs text-gray-500 ml-2">Wajib ada diagnosis dan minimal satu item.</span>
                    </form>
                @endif

                {{-- Fitur 27: keputusan pelanggan. --}}
                @if ($e->status === \App\Models\Estimasi::DIKIRIM && $aksiPutuskan)
                    <div class="grid gap-3 sm:grid-cols-2">
                        <form method="POST" action="{{ $aksiPutuskan['setujui']($e) }}" class="space-y-2">
                            @csrf
                            <textarea name="catatan" rows="2" class="block w-full border-gray-300 rounded-md text-sm"
                                placeholder="Catatan (opsional)">{{ old('catatan') }}</textarea>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-xs font-semibold uppercase rounded-md hover:bg-green-700">
                                Setujui estimasi
                            </button>
                        </form>
                        <form method="POST" action="{{ $aksiPutuskan['tolak']($e) }}" class="space-y-2"
                            onsubmit="return confirm('Tolak estimasi ini?')">
                            @csrf
                            <textarea name="catatan" rows="2" class="block w-full border-gray-300 rounded-md text-sm"
                                placeholder="Alasan penolakan (opsional)">{{ old('catatan') }}</textarea>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-xs font-semibold uppercase rounded-md hover:bg-red-700">
                                Tolak estimasi
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500">Belum ada estimasi.</p>

        @if ($aksiSimpan)
            <form method="POST" action="{{ $aksiSimpan }}" class="space-y-2 border-t border-gray-200 pt-4">
                @csrf
                <div data-baris-item class="space-y-2">
                    @include('servis._baris-estimasi', ['i' => 0, 'sumberTerpilih' => '', 'jumlah' => 1])
                </div>
                <template>
                    @include('servis._baris-estimasi', ['i' => '__i__', 'sumberTerpilih' => '', 'jumlah' => 1])
                </template>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" data-tambah-item
                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 text-xs font-semibold uppercase rounded-md hover:bg-gray-50">
                        Tambah baris
                    </button>
                    <x-primary-button>Simpan draf</x-primary-button>
                </div>
            </form>
        @endif
    @endforelse
</div>

@once
    <script>
        document.addEventListener('click', function (event) {
            const tombol = event.target.closest('[data-tambah-item]');
            if (!tombol) return;

            const form = tombol.closest('form');
            const wadah = form.querySelector('[data-baris-item]');
            const template = form.querySelector('template');
            if (!wadah || !template) return;

            const frag = template.content.cloneNode(true);
            frag.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace('__i__', wadah.children.length);
            });
            wadah.appendChild(frag);
        });
    </script>
@endonce