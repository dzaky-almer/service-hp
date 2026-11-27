{{-- Filter rentang tanggal laporan (fitur 47). $action = route tujuan, $filter = hasil controller. --}}
<form method="GET" action="{{ $action }}" class="bg-white shadow sm:rounded-lg p-4 flex flex-wrap items-end gap-3">
    <div>
        <x-input-label for="dari" value="Dari" />
        <x-text-input id="dari" name="dari" type="date" class="mt-1 block"
            :value="data_get($filter, 'dari')?->format('Y-m-d')" />
    </div>
    <div>
        <x-input-label for="sampai" value="Sampai" />
        <x-text-input id="sampai" name="sampai" type="date" class="mt-1 block"
            :value="data_get($filter, 'sampai')?->format('Y-m-d')" />
    </div>
    <x-primary-button>Terapkan</x-primary-button>
    @if ($export)
        <a href="{{ route('admin.laporan.export', ['jenis' => $export] + request()->only(['dari', 'sampai'])) }}"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-xs font-semibold uppercase rounded-md hover:bg-gray-50">
            Unduh CSV
        </a>
    @endif
</form>