<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_estimasi', function (Blueprint $table) {
            $table->id('id_item');
            $table->foreignId('id_estimasi')->constrained('estimasi', 'id_estimasi')->restrictOnDelete();
            $table->enum('jenis', ['jasa', 'sparepart']);
            $table->string('deskripsi', 150);
            $table->unsignedInteger('id_sparepart')->nullable();
            $table->unsignedInteger('id_tarif')->nullable();
            $table->integer('jumlah')->default(1);
            $table->decimal('harga_satuan_salinan', 12, 2);
        });

        DB::statement('ALTER TABLE item_estimasi ADD CONSTRAINT chk_item_jumlah CHECK (jumlah > 0)');
        DB::statement('ALTER TABLE item_estimasi ADD CONSTRAINT chk_item_harga CHECK (harga_satuan_salinan >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('item_estimasi');
    }
};
