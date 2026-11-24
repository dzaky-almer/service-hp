<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foto_servis', function (Blueprint $table) {
            $table->id('id_foto');
            $table->foreignId('id_servis')->constrained('servis', 'id_servis')->restrictOnDelete();
            $table->enum('jenis', ['masuk', 'hasil']);
            $table->string('path_file', 255);
            $table->foreignId('diunggah_oleh');
            $table->dateTime('waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foto_servis');
    }
};
