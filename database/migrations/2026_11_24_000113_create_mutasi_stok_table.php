<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_stok', function (Blueprint $table) {
            $table->id('id_mutasi');
            $table->foreignId('id_sparepart')->constrained('sparepart', 'id_sparepart')->restrictOnDelete();
            $table->enum('jenis', ['masuk', 'keluar_servis', 'penyesuaian']);
            $table->integer('jumlah');
            $table->foreignId('id_servis')->nullable();
            $table->string('alasan', 255)->nullable();
            $table->foreignId('dibuat_oleh');
            $table->dateTime('waktu');

            $table->index('id_sparepart', 'idx_mutasi_sparepart');
        });

        DB::statement('ALTER TABLE mutasi_stok ADD CONSTRAINT chk_mutasi_jumlah CHECK (jumlah <> 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_stok');
    }
};
