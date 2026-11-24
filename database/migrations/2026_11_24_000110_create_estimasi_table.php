<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimasi', function (Blueprint $table) {
            $table->id('id_estimasi');
            $table->foreignId('id_servis')->constrained('servis', 'id_servis')->restrictOnDelete();
            $table->smallInteger('versi');
            $table->enum('status', ['draf', 'dikirim', 'disetujui', 'ditolak', 'digantikan'])->default('draf');
            $table->unsignedInteger('dibuat_oleh');
            $table->unsignedInteger('divalidasi_oleh')->nullable();
            $table->dateTime('dikirim_pada')->nullable();
            $table->unsignedInteger('diputuskan_oleh')->nullable();
            $table->dateTime('diputuskan_pada')->nullable();
            $table->string('catatan_customer', 255)->nullable();
            $table->dateTime('dibuat_pada');

            $table->unique(['id_servis', 'versi'], 'uq_estimasi_versi');
            $table->index(['id_servis', 'status'], 'idx_estimasi_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimasi');
    }
};
