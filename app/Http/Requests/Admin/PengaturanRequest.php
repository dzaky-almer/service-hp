<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PengaturanRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_bisnis' => ['required', 'string', 'max:100'],
            'alamat_bisnis' => ['nullable', 'string', 'max:255'],
            'sla_hari' => ['required', 'integer', 'min:1', 'max:365'],
            'biaya_pemeriksaan' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_bisnis.required' => 'Nama bisnis wajib diisi.',
            'sla_hari.required' => 'SLA wajib diisi.',
            'sla_hari.integer' => 'SLA harus bilangan bulat (hari).',
            'sla_hari.min' => 'SLA minimal 1 hari.',
            'sla_hari.max' => 'SLA maksimal 365 hari.',
            'biaya_pemeriksaan.required' => 'Biaya pemeriksaan wajib diisi.',
            'biaya_pemeriksaan.numeric' => 'Biaya pemeriksaan harus berupa angka.',
            'biaya_pemeriksaan.min' => 'Biaya pemeriksaan tidak boleh negatif.',
        ];
    }
}
