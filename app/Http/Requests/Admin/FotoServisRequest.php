<?php

namespace App\Http\Requests\Admin;

use App\Models\FotoServis;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Upload foto perangkat (SECURITY.md §4 baris 46): whitelist tipe, periksa isi
 * (bukan ekstensi), batas 5 MB, nama file diacak oleh `store()`.
 */
class FotoServisRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'foto' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'jenis' => ['required', Rule::in(array_keys(FotoServis::LABEL_JENIS))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto.required' => 'Pilih file foto yang akan diunggah.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
            'jenis.in' => 'Jenis foto harus foto masuk atau foto hasil.',
        ];
    }
}
