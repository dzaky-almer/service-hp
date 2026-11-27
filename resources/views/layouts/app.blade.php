<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Pesan hasil aksi dan galat validasi, satu tempat untuk semua halaman -->
            @php
                $status = session('status');
                $statusKhusus = in_array($status, ['profile-updated', 'password-updated'], true);
            @endphp
            @if ($status && ! $statusKhusus)
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded" role="status">
                        {{ $status }}
                    </div>
                </div>
            @endif
            @if ($errors->any())
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded" role="alert">
                        <p class="font-semibold">Periksa kembali isian berikut:</p>
                        <ul class="mt-1 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $galat)
                                <li>{{ $galat }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
