<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_log', function (Blueprint $table) {
            $table->id('id_log');
            $table->string('penyedia', 30);
            $table->string('id_transaksi_eksternal', 100)->nullable();
            $table->json('payload');
            $table->boolean('tanda_tangan_valid');
            $table->dateTime('waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_log');
    }
};
