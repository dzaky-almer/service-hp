<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_status_servis', function (Blueprint $table) {
            $table->id('id_riwayat');
            $table->foreignId('id_servis')->constrained('servis', 'id_servis')->restrictOnDelete();
            $table->string('dari_status', 30)->nullable();
            $table->string('ke_status', 30);
            $table->foreignId('diubah_oleh')->nullable();
            $table->string('alasan', 255)->nullable();
            $table->dateTime('waktu');

            $table->index(['id_servis', 'waktu'], 'idx_riwayat_servis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_servis');
    }
};
