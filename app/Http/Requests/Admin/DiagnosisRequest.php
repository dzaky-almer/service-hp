<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Diagnosis teknisi (fitur 23). Kategori disimpan huruf kecil
 * (dirapikan di `DiagnosisService`).
 */
class DiagnosisRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'kategori_kerusakan' => ['required', 'string', 'max:60'],
            'hasil' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kategori_kerusakan.required' => 'Kategori kerusakan wajib diisi.',
            'kategori_kerusakan.max' => 'Kategori kerusakan maksimal 60 karakter.',
            'hasil.required' => 'Hasil diagnosis wajib diisi.',
            'hasil.max' => 'Hasil diagnosis maksimal 5000 karakter.',
        ];
    }
}
