{{-- Fitur 30, 31, 33, 34, 37: pengerjaan, catatan, spare part, QC. --}}
<div class="space-y-5">
    <div>
        <h4 class="text-sm font-semibold text-gray-800">Status pengerjaan</h4>
        <p class="mt-1 text-sm text-gray-600">Sekarang: <span class="font-medium">{{ $servis->labelStatus() }}</span></p>

        @if ($aksiStatus)
            <div class="mt-2 flex flex-wrap gap-2">
                @if ($servis->status === \App\Models\Servis::DISETUJUI)
                    <form method="POST" action="{{ $aksiStatus }}">
                        @csrf
                        <input type="hidden" name="status" value="dikerjakan">
                        <x-primary-button>Mulai pengerjaan</x-primary-button>
                    </form>
                @elseif ($servis->status === \App\Models\Servis::DIKERJAKAN)
                    <form method="POST" action="{{ $aksiStatus }}">
                        @csrf
                        <input type="hidden" name="status" value="quality_control">
                        <x-primary-button>Selesai perbaikan, kirim ke QC</x-primary-button>
                    </form>
                @endif
            </div>
            <x-input-error :messages="$errors->get('status')" />
        @endif

        @if ($aksiSiapDiambil)
            <form method="POST" action="{{ $aksiSiapDiambil }}" class="mt-2">
                @csrf
                <x-primary-button>Tandai siap diambil</x-primary-button>
            </form>
        @elseif ($servis->status === \App\Models\Servis::QUALITY_CONTROL)
            <p class="mt-2 text-xs text-gray-500">QC putaran terakhir belum lulus semua; tandai siap diambil baru bisa setelah lulus.</p>
        @endif
    </div>

    {{-- Catatan pengerjaan (fitur 31). --}}
    <div class="border-t border-gray-200 pt-4">
        <h4 class="text-sm font-semibold text-gray-800">Catatan pengerjaan</h4>
        <div class="mt-2 space-y-2">
            @forelse ($servis->catatan as $catatan)
                <div class="border border-gray-200 rounded-md p-2">
                    <div class="flex flex-wrap items-center justify-between gap-1 text-xs text-gray-500">
                        <span class="font-medium text-gray-700">{{ $catatan->penulis?->nama ?? '—' }}</span>
                        <span>{{ $catatan->waktu?->format('d/m/Y H:i') }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-800 whitespace-pre-line">{{ $catatan->isi }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500">Belum ada catatan.</p>
            @endforelse
        </div>

        @if ($aksiCatatan)
            <form method="POST" action="{{ $aksiCatatan }}" class="mt-3 space-y-2">
                @csrf
                <textarea name="isi" rows="2" required maxlength="2000" placeholder="mis. ganti LCD, uji sentuh"
                    class="block w-full border-gray-300 rounded-md text-sm">{{ old('isi') }}</textarea>
                <x-input-error :messages="$errors->get('isi')" />
                <x-primary-button>Tambah catatan</x-primary-button>
            </form>
        @endif
    </div>

    {{-- Pemakaian spare part (fitur 37). --}}
    <div class="border-t border-gray-200 pt-4">
        <h4 class="text-sm font-semibold text-gray-800">Spare part dipakai</h4>
        <table class="mt-2 min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase text-gray-500">
                    <th class="py-1">Spare part</th>
                    <th class="py-1">Jumlah</th>
                    <th class="py-1">Harga beli</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($servis->penggunaan as $pakai)
                    <tr>
                        <td class="py-1">{{ $pakai->sparepart?->nama ?? '—' }}</td>
                        <td class="py-1">{{ $pakai->jumlah }}</td>
                        <td class="py-1">{{ rupiah($pakai->harga_beli_salinan) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-1 text-gray-500">Belum ada spare part dicatat.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($aksiSparepart)
            <form method="POST" action="{{ $aksiSparepart }}" class="mt-3 flex flex-wrap items-end gap-2">
                @csrf
                <div class="grow">
                    <label for="id_sparepart" class="block text-sm text-gray-700">Spare part</label>
                    <select id="id_sparepart" name="id_sparepart" required class="block w-full border-gray-300 rounded-md text-sm">
                        <option value="">— pilih —</option>
                        @foreach ($daftarSparepart as $pilihan)
                            <option value="{{ $pilihan->id_sparepart }}" @selected(old('id_sparepart') == $pilihan->id_sparepart)>
                                {{ $pilihan->nama }} (stok {{ (int) $pilihan->stok }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('id_sparepart')" />
                </div>
                <div class="w-24">
                    <label for="jumlah" class="block text-sm text-gray-700">Jumlah</label>
                    <input id="jumlah" name="jumlah" type="number" min="1" max="999" value="{{ old('jumlah', 1) }}" required
                        class="block w-full border-gray-300 rounded-md text-sm">
                    <x-input-error :messages="$errors->get('jumlah')" />
                </div>
                <x-primary-button>Catat pemakaian</x-primary-button>
            </form>
        @endif
    </div>

    {{-- Quality control (fitur 33). --}}
    <div class="border-t border-gray-200 pt-4">
        <h4 class="text-sm font-semibold text-gray-800">Quality control</h4>
        @php($putaranTerakhir = $servis->qcPutaranTerakhir())
        @if ($putaranTerakhir === null)
            <p class="mt-1 text-sm text-gray-500">Belum ada pemeriksaan QC.</p>
        @else
            <div class="mt-2 space-y-2">
                @foreach ($servis->qc->groupBy('putaran') as $putaran => $butir)
                    <div class="border border-gray-200 rounded-md p-2">
                        <div class="flex flex-wrap items-center justify-between gap-1 text-xs text-gray-500">
                            <span class="font-medium text-gray-700">Putaran {{ $putaran }} — {{ $butir->every('lulus') ? 'lulus semua' : 'ada yang tidak lulus' }}</span>
                            <span>{{ $butir->first()->waktu?->format('d/m/Y H:i') }} · {{ $butir->first()->pemeriksa?->nama ?? '—' }}</span>
                        </div>
                        <ul class="mt-1 grid grid-cols-2 gap-x-4 text-sm text-gray-700">
                            @foreach ($butir as $baris)
                                <li>{{ $baris->butir }}: <span class="font-medium">{{ $baris->lulus ? 'lulus' : 'tidak lulus' }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($aksiQc)
            <form method="POST" action="{{ $aksiQc }}" class="mt-3 space-y-2">
                @csrf
                <p class="text-xs text-gray-500">Putaran {{ ($putaranTerakhir ?? 0) + 1 }}. Ada butir tidak lulus → servis kembali dikerjakan.</p>
                @foreach (\App\Services\PengerjaanService::BUTIR_QC as $butir)
                    <div class="flex items-center gap-4 text-sm">
                        <span class="w-24">{{ $butir }}</span>
                        <label class="inline-flex items-center gap-1">
                            <input type="radio" name="butir[{{ $butir }}]" value="lulus" @checked(old("butir.$butir", 'lulus') === 'lulus')>
                            Lulus
                        </label>
                        <label class="inline-flex items-center gap-1">
                            <input type="radio" name="butir[{{ $butir }}]" value="tidak_lulus" @checked(old("butir.$butir") === 'tidak_lulus')>
                            Tidak lulus
                        </label>
                    </div>
                @endforeach
                <x-input-error :messages="$errors->get('butir')" />
                <x-input-error :messages="$errors->get('status')" />
                <x-primary-button>Simpan hasil QC</x-primary-button>
            </form>
        @endif
    </div>
</div>