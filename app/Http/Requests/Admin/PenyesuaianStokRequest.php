<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Penyesuaian stok manual (fitur 38). Jumlah negatif = stok berkurang. */
class PenyesuaianStokRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'jumlah' => ['required', 'integer', 'not_in:0', 'between:-99999,99999'],
            'alasan' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jumlah.required' => 'Jumlah penyesuaian wajib diisi.',
            'jumlah.integer' => 'Jumlah penyesuaian harus bilangan bulat.',
            'jumlah.not_in' => 'Jumlah penyesuaian tidak boleh 0.',
            'alasan.required' => 'Alasan penyesuaian wajib diisi.',
            'alasan.max' => 'Alasan maksimal 255 karakter.',
        ];
    }
}
