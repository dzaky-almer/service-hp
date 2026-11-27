<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Pembalikan pembayaran (fitur 43): nominal ≤ sisa yang belum dibalik, alasan wajib. */
class PembalikanRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nominal' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'alasan' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nominal.required' => 'Nominal pembalikan wajib diisi.',
            'nominal.numeric' => 'Nominal pembalikan harus berupa angka.',
            'nominal.min' => 'Nominal pembalikan harus lebih dari 0.',
            'alasan.required' => 'Alasan pembalikan wajib diisi.',
            'alasan.max' => 'Alasan maksimal 255 karakter.',
        ];
    }
}
