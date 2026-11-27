<?php

namespace App\Http\Requests\Admin;

use App\Models\Pengambilan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Serah terima barang (fitur 44): penerima & metode verifikasi wajib. */
class SerahTerimaRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_penerima' => ['required', 'string', 'max:100'],
            'metode_verifikasi' => ['required', Rule::in(array_keys(Pengambilan::METODE))],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_penerima.required' => 'Nama penerima wajib diisi.',
            'nama_penerima.max' => 'Nama penerima maksimal 100 karakter.',
            'metode_verifikasi.required' => 'Metode verifikasi wajib dipilih.',
            'metode_verifikasi.in' => 'Metode verifikasi harus kode tracking atau identitas.',
            'catatan.max' => 'Catatan maksimal 255 karakter.',
        ];
    }
}
