<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penggunaan_sparepart', function (Blueprint $table) {
            $table->id('id_penggunaan');
            $table->foreignId('id_servis')->constrained('servis', 'id_servis')->restrictOnDelete();
            $table->foreignId('id_sparepart')->constrained('sparepart', 'id_sparepart')->restrictOnDelete();
            $table->foreignId('id_mutasi')->constrained('mutasi_stok', 'id_mutasi')->restrictOnDelete();
            $table->integer('jumlah');
            $table->decimal('harga_beli_salinan', 12, 2);
            $table->foreignId('dicatat_oleh');
            $table->dateTime('waktu');
        });

        DB::statement('ALTER TABLE penggunaan_sparepart ADD CONSTRAINT chk_penggunaan_jumlah CHECK (jumlah > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('penggunaan_sparepart');
    }
};
