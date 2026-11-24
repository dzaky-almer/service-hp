<?php

return [
    // Akun Owner awal (Tahap 0). Nilai default boleh diubah lewat .env.
    'owner_email' => env('SEED_OWNER_EMAIL', 'owner@servis.test'),
    'owner_password' => env('SEED_OWNER_PASSWORD', 'password'),

    // Pengaturan bisnis awal; kunci harus sama dengan yang dibaca aplikasi.
    'pengaturan_awal' => [
        'nama_bisnis' => env('APP_NAME', 'Sistem Manajemen Servis Barang'),
        'alamat_bisnis' => 'Jl. Contoh No. 1, Jakarta',
        'sla_hari' => '7',
        'biaya_pemeriksaan' => '0',
    ],
];
