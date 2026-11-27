<?php

namespace App\Http\Requests\Admin;

use App\Models\Sparepart;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SparepartRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $sparepart = $this->route('sparepart');

        return [
            'sku' => [
                'required', 'string', 'max:50',
                Rule::unique('sparepart', 'sku')->ignore($sparepart?->id_sparepart, 'id_sparepart'),
            ],
            'nama' => ['required', 'string', 'max:120'],
            'harga_beli' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'harga_jual' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'stok_minimum' => ['nullable', 'integer', 'min:0'],
            // Hanya dipakai saat membuat spare part baru; jadi mutasi stok masuk.
            'stok_awal' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in([Sparepart::STATUS_AKTIF, Sparepart::STATUS_NONAKTIF])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'SKU sudah dipakai spare part lain.',
            'stok_minimum.integer' => 'Stok minimum harus bilangan bulat.',
            'stok_awal.integer' => 'Stok awal harus bilangan bulat.',
            'harga_beli.numeric' => 'Harga beli harus berupa angka.',
            'harga_jual.numeric' => 'Harga jual harus berupa angka.',
            'harga_beli.min' => 'Harga tidak boleh negatif.',
            'harga_jual.min' => 'Harga tidak boleh negatif.',
            'stok_minimum.min' => 'Stok minimum tidak boleh negatif.',
            'stok_awal.min' => 'Stok awal tidak boleh negatif.',
            'status.in' => 'Status hanya boleh aktif atau nonaktif.',
        ];
    }
}
