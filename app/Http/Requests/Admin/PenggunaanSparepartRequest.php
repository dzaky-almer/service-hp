<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Pemakaian spare part pada servis (fitur 37). Harga diambil dari master, bukan form. */
class PenggunaanSparepartRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'id_sparepart' => ['required', 'integer', 'exists:sparepart,id_sparepart'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_sparepart.required' => 'Spare part wajib dipilih.',
            'id_sparepart.exists' => 'Spare part tidak ditemukan.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'jumlah.max' => 'Jumlah maksimal 999.',
        ];
    }
}
