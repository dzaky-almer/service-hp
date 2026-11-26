<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola Staf</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-1">Undang staf baru</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Akun berstatus Menunggu Aktivasi sampai penerima membuka tautan di email. Tautan berlaku
                    {{ \App\Models\UndanganAkun::MASA_BERLAKU_JAM }} jam dan hanya bisa dipakai sekali.
                </p>

                <form method="POST" action="{{ route('admin.staf.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf

                    <div>
                        <x-input-label for="nama" value="Nama" />
                        <x-text-input id="nama" name="nama" type="text" class="mt-1 block w-full"
                                      :value="old('nama')" required />
                        <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                      :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="no_hp" value="No. HP (opsional)" />
                        <x-text-input id="no_hp" name="no_hp" type="text" class="mt-1 block w-full"
                                      :value="old('no_hp')" />
                        <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="peran" value="Peran" />
                        <select id="peran" name="peran"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="teknisi" @selected(old('peran') === 'teknisi')>Teknisi</option>
                            <option value="admin" @selected(old('peran') === 'admin')>Admin</option>
                        </select>
                        <x-input-error :messages="$errors->get('peran')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-primary-button>Kirim undangan</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nama</th>
                            <th class="px-4 py-3 font-medium">Email</th>
                            <th class="px-4 py-3 font-medium">Peran</th>
                            <th class="px-4 py-3 font-medium">Status akun</th>
                            <th class="px-4 py-3 font-medium">Undangan</th>
                            <th class="px-4 py-3 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($staf as $orang)
                            @php($undanganTerakhir = $undangan->get($orang->id_pengguna))
                            <tr>
                                <td class="px-4 py-3">{{ $orang->nama }}</td>
                                <td class="px-4 py-3">{{ $orang->email }}</td>
                                <td class="px-4 py-3">{{ ucfirst($orang->peran) }}</td>
                                <td class="px-4 py-3">{{ $orang->labelStatusAkun() }}</td>
                                <td class="px-4 py-3">
                                    @if ($undanganTerakhir === null)
                                        -
                                    @elseif ($undanganTerakhir->dipakai_pada !== null)
                                        Dipakai {{ tanggal($undanganTerakhir->dipakai_pada) }}
                                    @elseif ($undanganTerakhir->kedaluwarsa_pada->isPast())
                                        Kedaluwarsa {{ tanggal($undanganTerakhir->kedaluwarsa_pada) }}
                                    @else
                                        Berlaku sampai {{ tanggal($undanganTerakhir->kedaluwarsa_pada) }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($orang->peran === \App\Models\Pengguna::PERAN_OWNER
                                        || $orang->status_akun === \App\Models\Pengguna::STATUS_MENUNGGU_AKTIVASI
                                        || $orang->is(auth()->user()))
                                        -
                                    @else
                                        @php($statusBaru = $orang->status_akun === \App\Models\Pengguna::STATUS_AKTIF
                                            ? \App\Models\Pengguna::STATUS_NONAKTIF
                                            : \App\Models\Pengguna::STATUS_AKTIF)
                                        <form method="POST" action="{{ route('admin.staf.status', $orang) }}">
                                            @csrf
                                            <input type="hidden" name="status_akun" value="{{ $statusBaru }}">
                                            <button type="submit" class="text-indigo-600 hover:underline">
                                                {{ $statusBaru === \App\Models\Pengguna::STATUS_AKTIF ? 'Aktifkan' : 'Nonaktifkan' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-3 text-gray-500" colspan="6">Belum ada staf.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
