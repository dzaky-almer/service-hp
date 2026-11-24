<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengambilan', function (Blueprint $table) {
            $table->id('id_pengambilan');
            $table->foreignId('id_servis')->unique('uq_pengambilan_servis')
                ->constrained('servis', 'id_servis')->restrictOnDelete();
            $table->foreignId('diserahkan_oleh');
            $table->string('nama_penerima', 100);
            $table->enum('metode_verifikasi', ['kode_tracking', 'identitas']);
            $table->string('catatan', 255)->nullable();
            $table->dateTime('waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengambilan');
    }
};
