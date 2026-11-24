<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id('id_pelanggan');
            $table->foreignId('id_pengguna')->nullable()->unique('uq_pelanggan_pengguna')
                ->constrained('pengguna', 'id_pengguna')->restrictOnDelete();
            $table->string('nama', 100);
            $table->string('no_hp', 20)->unique('uq_pelanggan_no_hp');
            $table->string('email', 150)->nullable();
            $table->text('alamat')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->dateTime('dibuat_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggan');
    }
};
