<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Draf estimasi (fitur 24). Form hanya mengirim sumber item dan jumlah; harga
 * selalu diambil dari master di server (BR-08).
 */
class EstimasiRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:40'],
            'items.*.sumber' => ['required', 'string', 'regex:/^(jasa|sparepart):\d+$/'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Tambahkan minimal satu item estimasi.',
            'items.min' => 'Tambahkan minimal satu item estimasi.',
            'items.max' => 'Item estimasi maksimal 40 baris.',
            'items.*.sumber.required' => 'Pilih jasa atau spare part untuk setiap item.',
            'items.*.sumber.regex' => 'Sumber item estimasi tidak dikenal.',
            'items.*.jumlah.required' => 'Jumlah item wajib diisi.',
            'items.*.jumlah.integer' => 'Jumlah item harus berupa angka.',
            'items.*.jumlah.min' => 'Jumlah item minimal 1.',
            'items.*.jumlah.max' => 'Jumlah item maksimal 999.',
        ];
    }
}
