<div class="flex flex-wrap items-start gap-2">
    <select name="items[{{ $i }}][sumber]" required class="flex-1 min-w-[16rem] border-gray-300 rounded-md text-sm">
        <option value="">— pilih jasa / spare part —</option>
        @foreach ($pilihanSumber as $nilai => $label)
            <option value="{{ $nilai }}" @selected($sumberTerpilih === $nilai)>{{ $label }}</option>
        @endforeach
    </select>
    <input type="number" name="items[{{ $i }}][jumlah]" value="{{ $jumlah }}" min="1" max="999" required
        class="w-24 border-gray-300 rounded-md text-sm" aria-label="Jumlah">
</div>