{{-- Fitur 39–43: tagihan, catat pembayaran, konfirmasi, pembalikan. --}}
@php
    $dibayar = $servis->totalDibayar();
    $tagihan = $servis->totalTagihan();
    $sisa = $servis->sisaTagihan();
    $daftarPembayaran = $servis->pembayaran()->with(['dibuatOleh', 'dikonfirmasiOleh'])->get();
@endphp

<div class="space-y-5">
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="border border-gray-200 rounded-md p-3">
            <p class="text-xs text-gray-500">Total tagihan</p>
            <p class="text-lg font-semibold text-gray-800">{{ rupiah($tagihan) }}</p>
        </div>
        <div class="border border-gray-200 rounded-md p-3">
            <p class="text-xs text-gray-500">Sudah dibayar</p>
            <p class="text-lg font-semibold text-gray-800">{{ rupiah($dibayar) }}</p>
        </div>
        <div class="border border-gray-200 rounded-md p-3">
            <p class="text-xs text-gray-500">Sisa</p>
            <p class="text-lg font-semibold {{ $sisa <= 0 ? 'text-green-700' : 'text-red-700' }}">{{ rupiah(max($sisa, 0)) }}</p>
            @if ($sisa <= 0 && $tagihan > 0)
                <span class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold rounded bg-green-100 text-green-800">Lunas</span>
            @endif
        </div>
    </div>

    @if ($tagihan <= 0)
        <p class="text-sm text-gray-500">Belum ada tagihan: estimasi belum disetujui pelanggan.</p>
    @endif

    @if ($aksiBayar && $sisa > 0)
        <form method="POST" action="{{ $aksiBayar }}" class="grid gap-2 sm:grid-cols-3 sm:items-end">
            @csrf
            <div>
                <x-input-label for="nominal" value="Nominal" />
                <x-text-input id="nominal" name="nominal" type="number" step="0.01" min="0.01" class="mt-1 block w-full"
                    :value="old('nominal', number_format($sisa, 2, '.', ''))" />
                <x-input-error :messages="$errors->get('nominal')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="metode" value="Metode" />
                <select id="metode" name="metode" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @foreach ($metodePilihan as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('metode') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('metode')" class="mt-1" />
            </div>
            <div>
                <x-primary-button>Catat pembayaran</x-primary-button>
            </div>
        </form>
        <p class="text-xs text-gray-500">Pembayaran gateway menunggu webhook; tunai/transfer menunggu konfirmasi Admin.</p>
    @endif

    <div class="border-t border-gray-200 pt-4">
        <h4 class="text-sm font-semibold text-gray-800">Riwayat pembayaran</h4>
        <div class="mt-2 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500">
                        <th class="py-1 pr-3">Waktu</th>
                        <th class="py-1 pr-3">Jenis</th>
                        <th class="py-1 pr-3">Metode</th>
                        <th class="py-1 pr-3 text-right">Nominal</th>
                        <th class="py-1 pr-3">Status</th>
                        <th class="py-1">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarPembayaran as $bayar)
                        <tr class="border-t border-gray-100 align-top">
                            <td class="py-2 pr-3 text-gray-600">{{ $bayar->dibuat_pada?->format('d/m/Y H:i') }}</td>
                            <td class="py-2 pr-3">
                                @if ($bayar->jenis === \App\Models\Pembayaran::PEMBALIKAN)
                                    Pembalikan #{{ $bayar->id_pembayaran_asal }}
                                    @if ($bayar->alasan)<span class="block text-xs text-gray-500">{{ $bayar->alasan }}</span>@endif
                                @else
                                    Bayar
                                @endif
                            </td>
                            <td class="py-2 pr-3">{{ $bayar->labelMetode() }}</td>
                            <td class="py-2 pr-3 text-right">{{ rupiah((float) $bayar->nominal) }}</td>
                            <td class="py-2 pr-3">
                                <span class="px-2 py-0.5 text-xs rounded
                                    {{ $bayar->status === \App\Models\Pembayaran::BERHASIL ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $bayar->status === \App\Models\Pembayaran::MENUNGGU ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $bayar->status === \App\Models\Pembayaran::GAGAL ? 'bg-gray-100 text-gray-700' : '' }}">
                                    {{ \App\Models\Pembayaran::LABEL_STATUS[$bayar->status] ?? $bayar->status }}
                                </span>
                                @if ($bayar->alasan && $bayar->jenis === \App\Models\Pembayaran::BAYAR)
                                    <span class="block text-xs text-gray-500">{{ $bayar->alasan }}</span>
                                @endif
                                @if ($bayar->id_transaksi_eksternal)
                                    <span class="block text-xs text-gray-500">{{ $bayar->id_transaksi_eksternal }}</span>
                                @endif
                            </td>
                            <td class="py-2">
                                @if ($aksiKelola && $bayar->status === \App\Models\Pembayaran::MENUNGGU)
                                    <div class="flex flex-wrap items-start gap-2">
                                        <form method="POST" action="{{ route('admin.pembayaran.konfirmasi', $bayar) }}">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-green-700 hover:underline">Konfirmasi</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.pembayaran.tolak', $bayar) }}" class="flex items-start gap-1">
                                            @csrf
                                            <input type="text" name="alasan" placeholder="Alasan" maxlength="255"
                                                class="w-28 border-gray-300 rounded-md text-xs py-1">
                                            <button type="submit" class="text-xs font-semibold text-red-700 hover:underline">Tolak</button>
                                        </form>
                                    </div>
                                @elseif ($aksiKelola && $bayar->jenis === \App\Models\Pembayaran::BAYAR && $bayar->status === \App\Models\Pembayaran::BERHASIL && ! $servis->pengambilan()->exists())
                                    <details>
                                        <summary class="text-xs font-semibold text-gray-700 cursor-pointer">Pembalikan</summary>
                                        <form method="POST" action="{{ route('admin.pembayaran.pembalikan', $bayar) }}" class="mt-1 flex flex-wrap items-start gap-1">
                                            @csrf
                                            <input type="number" name="nominal" step="0.01" min="0.01" max="{{ (float) $bayar->nominal }}"
                                                value="{{ number_format($bayar->sisaDapatDibalik(), 2, '.', '') }}"
                                                class="w-24 border-gray-300 rounded-md text-xs py-1">
                                            <input type="text" name="alasan" placeholder="Alasan" maxlength="255" required
                                                class="w-32 border-gray-300 rounded-md text-xs py-1">
                                            <button type="submit" class="text-xs font-semibold text-red-700 hover:underline">Simpan</button>
                                        </form>
                                        <p class="mt-1 text-xs text-gray-500">Admin hanya hari yang sama; selain itu Owner.</p>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-2 text-gray-500">Belum ada pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>