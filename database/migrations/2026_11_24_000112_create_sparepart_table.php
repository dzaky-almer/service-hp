<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sparepart', function (Blueprint $table) {
            $table->id('id_sparepart');
            $table->string('sku', 50)->unique('uq_sparepart_sku');
            $table->string('nama', 120);
            $table->decimal('harga_beli', 12, 2)->default(0);
            $table->decimal('harga_jual', 12, 2);
            $table->integer('stok_minimum')->default(0);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
        });

        DB::statement('ALTER TABLE sparepart ADD CONSTRAINT chk_sparepart_harga CHECK (harga_beli >= 0 AND harga_jual >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('sparepart');
    }
};
