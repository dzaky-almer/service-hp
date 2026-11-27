<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Checklist QC (fitur 33): setiap butir lulus atau tidak lulus. */
class QcRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'butir' => ['required', 'array'],
            'butir.*' => ['required', 'in:lulus,tidak_lulus'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'butir.required' => 'Checklist QC wajib diisi.',
            'butir.*.required' => 'Setiap butir QC harus dipilih.',
            'butir.*.in' => 'Nilai butir QC harus lulus atau tidak lulus.',
        ];
    }
}
