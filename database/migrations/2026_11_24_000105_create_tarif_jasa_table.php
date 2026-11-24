<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif_jasa', function (Blueprint $table) {
            $table->id('id_tarif');
            $table->string('nama', 100);
            $table->decimal('harga', 12, 2);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
        });

        DB::statement('ALTER TABLE tarif_jasa ADD CONSTRAINT chk_tarif_harga CHECK (harga >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_jasa');
    }
};
