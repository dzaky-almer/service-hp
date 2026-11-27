<?php

namespace App\Http\Requests\Admin;

use App\Models\Pelanggan;
use App\Models\Pengguna;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PelangganRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $pelanggan = $this->route('pelanggan');
        $undang = $this->boolean('kirim_undangan');

        return [
            'nama' => ['required', 'string', 'max:100'],
            'no_hp' => [
                'required', 'string', 'max:20',
                Rule::unique('pelanggan', 'no_hp')->ignore($pelanggan?->id_pelanggan, 'id_pelanggan'),
                $this->bukanMilikAkunLain($pelanggan),
            ],
            // Email hanya wajib bila undangan akun diminta (fitur 11).
            'email' => array_merge(
                $undang ? ['required'] : ['nullable'],
                ['string', 'lowercase', 'email', 'max:150'],
                $undang ? [Rule::unique('pengguna', 'email')] : [],
            ),
            'alamat' => ['nullable', 'string', 'max:1000'],
            'kirim_undangan' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_hp.unique' => 'No. HP sudah terdaftar.',
            'email.required' => 'Email wajib diisi untuk mengirim undangan akun.',
            'email.unique' => 'Email sudah terdaftar.',
        ];
    }

    /**
     * No. HP pelanggan tidak boleh dipakai akun pengguna lain. No. HP akun milik
     * pelanggan itu sendiri (hasil undangan) tetap boleh.
     */
    protected function bukanMilikAkunLain(?Pelanggan $pelanggan): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($pelanggan) {
            $dipakaiAkunLain = Pengguna::where('no_hp', $value)
                ->where('id_pengguna', '!=', $pelanggan?->id_pengguna)
                ->exists();

            if ($dipakaiAkunLain) {
                $fail('No. HP sudah dipakai akun pengguna lain.');
            }
        };
    }
}
