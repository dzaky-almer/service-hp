<?php

namespace App\Http\Requests\Admin;

use App\Models\Pelanggan;
use App\Models\Pengguna;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UndangStafRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', Rule::unique(Pengguna::class)],
            'no_hp' => ['nullable', 'string', 'max:20', $this->noHpUnik()],
            // Owner tidak diundang: dibuat lewat seed / `php artisan owner:buat` (TECHSTACK, SECURITY §1).
            'peran' => ['required', Rule::in([Pengguna::PERAN_ADMIN, Pengguna::PERAN_TEKNISI])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah terdaftar.',
            'peran.in' => 'Peran hanya boleh admin atau teknisi.',
        ];
    }

    /**
     * No. HP tidak boleh dipakai pengguna lain atau pelanggan tanpa akun.
     */
    protected function noHpUnik(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $dipakai = Pengguna::where('no_hp', $value)->exists()
                || Pelanggan::where('no_hp', $value)->exists();

            if ($dipakai) {
                $fail('No. HP sudah terdaftar.');
            }
        };
    }
}
