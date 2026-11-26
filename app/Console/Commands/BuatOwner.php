<?php

namespace App\Console\Commands;

use App\Services\StafService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class BuatOwner extends Command
{
    protected $signature = 'owner:buat
                            {email? : Email akun Owner}
                            {--nama= : Nama akun Owner}
                            {--password= : Password akun (bila kosong, ditanyakan tersembunyi)}';

    protected $description = 'Buat akun Owner baru (SECURITY.md §1: tidak lewat halaman publik)';

    public function handle(StafService $stafService): int
    {
        $nama = $this->option('nama') ?: $this->ask('Nama');
        $email = $this->argument('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password');

        // Konfirmasi hanya relevan saat password diketik manual.
        if ($this->option('password') === null && $this->secret('Ulangi password') !== $password) {
            $this->error('Konfirmasi password tidak cocok.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['nama' => $nama, 'email' => $email, 'password' => $password],
            [
                'nama' => ['required', 'string', 'max:100'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:pengguna,email'],
                'password' => ['required', Password::defaults()],
            ],
            [
                'nama.required' => 'Nama wajib diisi.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.lowercase' => 'Email harus huruf kecil.',
                'email.unique' => 'Email sudah terdaftar.',
                'password.required' => 'Password wajib diisi.',
                'password.min' => 'Password minimal :min karakter.',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $pesan) {
                $this->error($pesan);
            }

            return self::FAILURE;
        }

        $pengguna = $stafService->buatOwner($nama, $email, $password);

        $this->info('Akun Owner '.$pengguna->email.' dibuat dan berstatus aktif.');

        return self::SUCCESS;
    }
}
