<?php

namespace App\Http\Requests\Admin;

use App\Models\Pelanggan;
use App\Models\Servis;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ServisRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_pelanggan' => [$this->isMethod('post') ? 'required' : 'nullable', 'integer', Rule::exists('pelanggan', 'id_pelanggan')],
            'jenis_perangkat' => ['required', Rule::in(array_keys(Servis::JENIS_PERANGKAT))],
            'merek' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:100'],
            'nomor_seri' => ['nullable', 'string', 'max:100'],
            'keluhan' => ['required', 'string', 'max:2000'],
            'kondisi_fisik' => ['nullable', 'string', 'max:1000'],
            'kelengkapan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $pelanggan = Pelanggan::find($this->input('id_pelanggan'));

                if ($pelanggan !== null && $pelanggan->status !== Pelanggan::STATUS_AKTIF) {
                    $validator->errors()->add('id_pelanggan', 'Pelanggan nonaktif tidak dapat dibuatkan servis.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_pelanggan.required' => 'Pelanggan wajib dipilih.',
            'id_pelanggan.exists' => 'Pelanggan tidak ditemukan.',
            'jenis_perangkat.required' => 'Jenis perangkat wajib dipilih.',
            'jenis_perangkat.in' => 'Jenis perangkat tidak dikenal.',
            'merek.required' => 'Merek wajib diisi.',
            'model.required' => 'Model wajib diisi.',
            'keluhan.required' => 'Keluhan wajib diisi.',
        ];
    }
}
