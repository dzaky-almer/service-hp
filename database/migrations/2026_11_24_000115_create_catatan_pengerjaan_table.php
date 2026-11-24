<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_pengerjaan', function (Blueprint $table) {
            $table->id('id_catatan');
            $table->foreignId('id_servis')->constrained('servis', 'id_servis')->restrictOnDelete();
            $table->foreignId('id_pengguna');
            $table->text('isi');
            $table->dateTime('waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_pengerjaan');
    }
};
