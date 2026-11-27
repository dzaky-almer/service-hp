<x-publik-layout>
    <div class="bg-white shadow sm:rounded-lg p-8">
        <h1 class="text-2xl font-semibold text-gray-900">{{ $namaBisnis }}</h1>
        @if ($alamatBisnis)
            <p class="text-gray-600 mt-2">{{ $alamatBisnis }}</p>
        @endif

        <dl class="mt-6 grid gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-gray-500">Target penyelesaian</dt>
                <dd class="text-gray-900 font-medium">{{ $slaHari }} hari kerja</dd>
            </div>
            <div>
                <dt class="text-gray-500">Biaya pemeriksaan</dt>
                <dd class="text-gray-900 font-medium">
                    @if ($biayaPemeriksaan > 0)
                        Rp {{ number_format($biayaPemeriksaan, 0, ',', '.') }}
                    @else
                        Gratis
                    @endif
                </dd>
            </div>
        </dl>

        <a href="{{ route('publik.lacak.index') }}"
            class="mt-8 inline-flex items-center px-5 py-2.5 bg-gray-800 text-white text-sm font-semibold rounded-md hover:bg-gray-700">
            Lacak status servis
        </a>
    </div>
</x-publik-layout>