<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pelanggan</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Fitur 11: tambah pelanggan, opsional sekaligus undang akunnya. --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800">Pelanggan baru</h3>
                <p class="text-sm text-gray-500 mt-1 mb-4">
                    Pelanggan bisa dicatat tanpa akun. Bila undangan akun dicentang, akun dibuat berstatus
                    Menunggu Aktivasi sampai tautan di email dibuka pelanggan.
                </p>

                <form method="POST" action="{{ route('admin.pelanggan.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf

                    <div>
                        <x-input-label for="nama" value="Nama" />
                        <x-text-input id="nama" name="nama" type="text" class="mt-1 block w-full" :value="old('nama')" required />
                        <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="no_hp" value="No. HP" />
                        <x-text-input id="no_hp" name="no_hp" type="text" class="mt-1 block w-full" :value="old('no_hp')" required />
                        <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="alamat" value="Alamat" />
                        <x-text-input id="alamat" name="alamat" type="text" class="mt-1 block w-full" :value="old('alamat')" />
                        <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="kirim_undangan" value="1" @checked(old('kirim_undangan'))>
                            Kirim undangan akun ke email di atas
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <x-primary-button>Simpan pelanggan</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Fitur 12: daftar + pencarian; fitur 13: ubah; fitur 14: nonaktifkan. --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="GET" action="{{ route('admin.pelanggan.index') }}" class="flex gap-2 mb-4">
                    <x-text-input name="cari" type="text" class="block w-full" :value="$cari" placeholder="Cari nama, no. HP, atau email" />
                    <x-secondary-button type="submit">Cari</x-secondary-button>
                    @if ($cari !== '')
                        <a href="{{ route('admin.pelanggan.index') }}" class="inline-flex items-center text-sm text-gray-600 px-2">Reset</a>
                    @endif
                </form>

                <table class="w-full text-sm text-left">
                    <thead class="text-gray-500 border-b">
                        <tr>
                            <th class="py-2">Nama</th>
                            <th class="py-2">No. HP</th>
                            <th class="py-2">Email</th>
                            <th class="py-2">Akun</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pelanggan as $item)
                            <tr class="border-b align-top">
                                <td class="py-2">{{ $item->nama }}</td>
                                <td class="py-2">{{ $item->no_hp }}</td>
                                <td class="py-2">{{ $item->email ?? '-' }}</td>
                                <td class="py-2">{{ $item->pengguna?->labelStatusAkun() ?? 'Belum ada' }}</td>
                                <td class="py-2">{{ $item->labelStatus() }}</td>
                                <td class="py-2">
                                    <details>
                                        <summary class="cursor-pointer text-indigo-600">Ubah</summary>

                                        <form method="POST" action="{{ route('admin.pelanggan.update', $item) }}"
                                              class="grid gap-3 mt-3 min-w-[22rem]">
                                            @csrf
                                            @method('PUT')

                                            <div>
                                                <x-input-label for="nama-{{ $item->id_pelanggan }}" value="Nama" />
                                                <x-text-input id="nama-{{ $item->id_pelanggan }}" name="nama" type="text"
                                                              class="mt-1 block w-full" :value="old('nama', $item->nama)" required />
                                            </div>

                                            <div>
                                                <x-input-label for="no_hp-{{ $item->id_pelanggan }}" value="No. HP" />
                                                <x-text-input id="no_hp-{{ $item->id_pelanggan }}" name="no_hp" type="text"
                                                              class="mt-1 block w-full" :value="old('no_hp', $item->no_hp)" required />
                                            </div>

                                            <div>
                                                <x-input-label for="email-{{ $item->id_pelanggan }}" value="Email" />
                                                <x-text-input id="email-{{ $item->id_pelanggan }}" name="email" type="email"
                                                              class="mt-1 block w-full" :value="old('email', $item->email)" />
                                            </div>

                                            <div>
                                                <x-input-label for="alamat-{{ $item->id_pelanggan }}" value="Alamat" />
                                                <x-text-input id="alamat-{{ $item->id_pelanggan }}" name="alamat" type="text"
                                                              class="mt-1 block w-full" :value="old('alamat', $item->alamat)" />
                                            </div>

                                            @if ($item->id_pengguna === null)
                                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" name="kirim_undangan" value="1">
                                                    Kirim undangan akun ke email di atas
                                                </label>
                                            @endif

                                            <div>
                                                <x-primary-button>Simpan</x-primary-button>
                                            </div>
                                        </form>

                                        @if ($item->status === \App\Models\Pelanggan::STATUS_AKTIF)
                                            <form method="POST" action="{{ route('admin.pelanggan.nonaktif', $item) }}" class="mt-3">
                                                @csrf
                                                <x-danger-button>Nonaktifkan</x-danger-button>
                                            </form>
                                        @endif
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-gray-500">Tidak ada pelanggan yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $pelanggan->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
