<?php

namespace App\Policies;

use App\Models\Pengguna;
use App\Models\Servis;

/**
 * Izin per objek servis (SECURITY.md §2): peran + kepemilikan/penugasan.
 */
class ServisPolicy
{
    public function lihat(Pengguna $pengguna, Servis $servis): bool
    {
        return match ($pengguna->peran) {
            Pengguna::PERAN_ADMIN, Pengguna::PERAN_OWNER => true,
            Pengguna::PERAN_TEKNISI => $servis->id_teknisi === $pengguna->id_pengguna,
            Pengguna::PERAN_CUSTOMER => $this->pemilik($pengguna, $servis),
            default => false,
        };
    }

    /** Ubah info servis (fitur 18): Admin/Owner siapa saja, teknisi hanya yang ditugaskan. */
    public function ubah(Pengguna $pengguna, Servis $servis): bool
    {
        return $this->lihat($pengguna, $servis)
            && in_array($pengguna->peran, [Pengguna::PERAN_ADMIN, Pengguna::PERAN_OWNER, Pengguna::PERAN_TEKNISI], true);
    }

    /** Assign teknisi, batalkan: hanya Admin dan Owner (batas status di Service). */
    public function kelola(Pengguna $pengguna, Servis $servis): bool
    {
        return in_array($pengguna->peran, [Pengguna::PERAN_ADMIN, Pengguna::PERAN_OWNER], true);
    }

    /** Diagnosis & draf estimasi (fitur 23, 24): teknisi ditugaskan, Admin, Owner. */
    public function diagnosis(Pengguna $pengguna, Servis $servis): bool
    {
        return $this->ubah($pengguna, $servis);
    }

    /** Kirim estimasi ke customer (fitur 25): Admin/Owner. */
    public function kirimEstimasi(Pengguna $pengguna, Servis $servis): bool
    {
        return in_array($pengguna->peran, [Pengguna::PERAN_ADMIN, Pengguna::PERAN_OWNER], true);
    }

    /** Setujui/tolak estimasi (fitur 27): customer pemilik servis saja. */
    public function putuskanEstimasi(Pengguna $pengguna, Servis $servis): bool
    {
        return $pengguna->peran === Pengguna::PERAN_CUSTOMER && $this->pemilik($pengguna, $servis);
    }

    private function pemilik(Pengguna $pengguna, Servis $servis): bool
    {
        return $servis->pelanggan?->id_pengguna === $pengguna->id_pengguna;
    }
}
