<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_hasil', function (Blueprint $table) {
            $table->id('id_qc');
            $table->foreignId('id_servis')->constrained('servis', 'id_servis')->restrictOnDelete();
            $table->smallInteger('putaran');
            $table->string('butir', 80);
            $table->boolean('lulus');
            $table->foreignId('dicek_oleh');
            $table->dateTime('waktu');

            $table->index(['id_servis', 'putaran'], 'idx_qc_servis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_hasil');
    }
};
