<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id('id_audit');
            $table->foreignId('id_pengguna')->nullable();
            $table->string('aksi', 60);
            $table->string('objek_tipe', 40);
            $table->integer('objek_id');
            $table->json('data_sebelum')->nullable();
            $table->json('data_sesudah')->nullable();
            $table->string('ip', 45)->nullable();
            $table->dateTime('waktu');

            $table->index(['objek_tipe', 'objek_id'], 'idx_audit_objek');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
