<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('undangan_akun', function (Blueprint $table) {
            $table->id('id_undangan');
            $table->foreignId('id_pengguna')->constrained('pengguna', 'id_pengguna')->restrictOnDelete();
            $table->char('token_hash', 64)->unique('uq_undangan_token');
            $table->dateTime('kedaluwarsa_pada');
            $table->dateTime('dipakai_pada')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('pengguna', 'id_pengguna')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('undangan_akun');
    }
};
