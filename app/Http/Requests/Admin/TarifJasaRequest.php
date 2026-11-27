<?php

namespace App\Http\Requests\Admin;

use App\Models\TarifJasa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TarifJasaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'harga' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'status' => ['nullable', Rule::in([TarifJasa::STATUS_AKTIF, TarifJasa::STATUS_NONAKTIF])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'harga.numeric' => 'Harga harus berupa angka.',
            'harga.min' => 'Harga tidak boleh negatif.',
            'status.in' => 'Status hanya boleh aktif atau nonaktif.',
        ];
    }
}
