<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Ubah status pengerjaan (fitur 30): hanya dua langkah dari layar pengerjaan. */
class UbahStatusRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:dikerjakan,quality_control'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status tujuan wajib dipilih.',
            'status.in' => 'Status tujuan tidak dikenal.',
        ];
    }
}
