<?php

use Illuminate\Support\Carbon;

/**
 * Format mata uang tampilan: 850000 -> "Rp850.000".
 */
function rupiah(int|float $nilai): string
{
    return 'Rp'.number_format($nilai, 0, ',', '.');
}

/**
 * Format tanggal tampilan WIB: "24 Nov 2026, 14:30".
 */
function tanggal(DateTimeInterface|string|null $waktu): string
{
    return $waktu === null ? '-' : Carbon::parse($waktu)->translatedFormat('d M Y, H:i');
}
