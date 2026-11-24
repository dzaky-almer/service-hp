<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis', function (Blueprint $table) {
            $table->id('id_diagnosis');
            $table->foreignId('id_servis')->constrained('servis', 'id_servis')->restrictOnDelete();
            $table->smallInteger('versi');
            $table->string('kategori_kerusakan', 60);
            $table->text('hasil');
            $table->foreignId('dibuat_oleh');
            $table->dateTime('waktu');

            $table->unique(['id_servis', 'versi'], 'uq_diagnosis_versi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis');
    }
};
