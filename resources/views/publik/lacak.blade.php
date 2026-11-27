<x-publik-layout>
    <div class="max-w-md mx-auto bg-white shadow sm:rounded-lg p-8">
        <h1 class="text-xl font-semibold text-gray-900">Lacak servis</h1>
        <p class="text-sm text-gray-600 mt-2">Masukkan kode tracking dari tanda terima Anda (12 karakter).</p>

        <form method="POST" action="{{ route('publik.lacak.cari') }}" class="mt-6 space-y-3">
            @csrf
            <x-text-input id="kode_tracking" name="kode_tracking" type="text" class="block w-full font-mono uppercase"
                :value="old('kode_tracking')" placeholder="mis. K7XYZ3ABCDEF" required autofocus />
            <x-input-error :messages="$errors->get('kode_tracking')" />
            <x-primary-button class="w-full justify-center">Lacak</x-primary-button>
        </form>
    </div>
</x-publik-layout>