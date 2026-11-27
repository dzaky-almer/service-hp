<?php

namespace App\Http\Requests\Admin;

use App\Models\Pengguna;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Field tambahan pada satu form detail servis: assign teknisi dan pembatalan.
 * Validasi nilai status/peran sebenarnya tetap di `ServisService`.
 */
class KelolaServisRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_teknisi' => ['sometimes', 'required', 'integer', Rule::exists('pengguna', 'id_pengguna')],
            'alasan' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->has('id_teknisi')) {
                    return;
                }

                $peran = Pengguna::whereKey($this->input('id_teknisi'))->value('peran');

                if ($peran !== Pengguna::PERAN_TEKNISI) {
                    $validator->errors()->add('id_teknisi', 'Akun yang dipilih bukan teknisi.');
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
            'id_teknisi.required' => 'Teknisi wajib dipilih.',
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
        ];
    }
}
