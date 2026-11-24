<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->foreignId('id_servis')->constrained('servis', 'id_servis')->restrictOnDelete();
            $table->enum('jenis', ['bayar', 'pembalikan'])->default('bayar');
            $table->unsignedBigInteger('id_pembayaran_asal')->nullable();
            $table->enum('metode', ['tunai', 'transfer', 'gateway']);
            $table->decimal('nominal', 12, 2);
            $table->enum('status', ['menunggu', 'berhasil', 'gagal'])->default('menunggu');
            $table->string('id_transaksi_eksternal', 100)->nullable()->unique('uq_pembayaran_eksternal');
            $table->string('bukti_path', 255)->nullable();
            $table->foreignId('dibuat_oleh');
            $table->foreignId('dikonfirmasi_oleh')->nullable();
            $table->string('alasan', 255)->nullable();
            $table->dateTime('dibuat_pada');
            $table->dateTime('diputuskan_pada')->nullable();

            $table->index(['id_servis', 'status'], 'idx_pembayaran_servis');

            $table->foreign('id_pembayaran_asal')->references('id_pembayaran')->on('pembayaran')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE pembayaran ADD CONSTRAINT chk_pembayaran_nominal CHECK (nominal > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
