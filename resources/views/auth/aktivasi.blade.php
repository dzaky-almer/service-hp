<x-guest-layout>
    <h1 class="text-lg font-semibold text-gray-900">{{ __('Aktivasi akun') }}</h1>

    <p class="mt-2 text-sm text-gray-600">
        {{ __('Halo :nama, tetapkan password untuk mengaktifkan akun Anda.', ['nama' => $pengguna->nama]) }}
    </p>

    <p class="mt-1 text-sm text-gray-600">{{ $pengguna->email }}</p>

    <form method="POST" action="{{ route('aktivasi.store', ['token' => $token]) }}" class="mt-4">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password baru')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Ulangi password baru')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Aktifkan akun') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
