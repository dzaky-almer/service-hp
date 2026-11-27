<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Servis baru</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">
                    Nomor servis dan kode tracking dibuat otomatis. SLA dan biaya pemeriksaan disalin
                    dari pengaturan bisnis saat ini, jadi perubahan pengaturan tidak mengubah servis lama.
                </p>

                @include('admin.servis._form', [
                    'aksi' => route('admin.servis.store'),
                    'metode' => 'POST',
                    'servis' => null,
                    'tombol' => 'Buat servis',
                ])
            </div>
        </div>
    </div>
</x-app-layout>