<?php

namespace App\Http\Requests;

use App\Models\Pelanggan;
use App\Models\Pengguna;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
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
            'no_hp' => ['nullable', 'string', 'max:20', $this->noHpUnik()],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:150',
                Rule::unique(Pengguna::class)->ignore($this->user()),
            ],
        ];
    }

    /**
     * No. HP tidak boleh dipakai pengguna lain atau pelanggan tanpa akun.
     */
    protected function noHpUnik(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $id = $this->user()->id_pengguna;

            $dipakaiPengguna = Pengguna::where('no_hp', $value)->whereKeyNot($id)->exists();
            $dipakaiPelanggan = Pelanggan::where('no_hp', $value)
                ->where(fn ($q) => $q->whereNull('id_pengguna')->orWhere('id_pengguna', '!=', $id))
                ->exists();

            if ($dipakaiPengguna || $dipakaiPelanggan) {
                $fail('No. HP sudah terdaftar.');
            }
        };
    }
}
