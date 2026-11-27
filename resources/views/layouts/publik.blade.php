<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $judul ?? \App\Models\PengaturanBisnis::nilai('nama_bisnis', config('app.name')) }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="min-h-screen">
            <header class="bg-white shadow">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                    <a href="{{ route('publik.beranda') }}" class="font-semibold text-gray-900">
                        {{ \App\Models\PengaturanBisnis::nilai('nama_bisnis', config('app.name')) }}
                    </a>
                    <nav class="flex items-center gap-4 text-sm">
                        <a href="{{ route('publik.lacak.index') }}" class="text-gray-600 hover:underline">Lacak servis</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="text-blue-700 hover:underline">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-blue-700 hover:underline">Masuk</a>
                        @endauth
                    </nav>
                </div>
            </header>

            <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>