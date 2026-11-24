<?php

use Illuminate\Support\Carbon;

it('memformat rupiah tanpa desimal', function () {
    expect(rupiah(850000))->toBe('Rp850.000')
        ->and(rupiah(0))->toBe('Rp0')
        ->and(rupiah(1234567.4))->toBe('Rp1.234.567');
});

it('memformat tanggal WIB', function () {
    expect(tanggal('2026-11-24 14:30:00'))->toBe('24 Nov 2026, 14:30')
        ->and(tanggal(Carbon::parse('2026-01-05 09:00:00')))->toBe('05 Jan 2026, 09:00')
        ->and(tanggal(null))->toBe('-');
});
