<?php

namespace App\Http\Requests\Admin;

use App\Models\Pembayaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Buat pembayaran (fitur 40). Nominal & sisa tagihan diperiksa ulang di service. */
class PembayaranRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'metode' => ['required', Rule::in(array_keys(Pembayaran::METODE))],
            'nominal' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'metode.required' => 'Metode pembayaran wajib dipilih.',
            'metode.in' => 'Metode pembayaran tidak dikenal.',
            'nominal.required' => 'Nominal pembayaran wajib diisi.',
            'nominal.numeric' => 'Nominal pembayaran harus berupa angka.',
            'nominal.min' => 'Nominal pembayaran harus lebih dari 0.',
        ];
    }
}
