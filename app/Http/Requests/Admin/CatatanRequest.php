<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Catatan pengerjaan (fitur 31). */
class CatatanRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'isi' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'isi.required' => 'Catatan wajib diisi.',
            'isi.max' => 'Catatan maksimal 2000 karakter.',
        ];
    }
}
