<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urutan_nomor', function (Blueprint $table) {
            $table->smallInteger('tahun')->primary();
            $table->integer('terakhir')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urutan_nomor');
    }
};
