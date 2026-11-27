<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pembayaran gateway menggantung (fitur 42/43).
Schedule::command('pembayaran:kedaluwarsa')->hourly()->withoutOverlapping();
