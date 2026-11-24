<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servis', function (Blueprint $table) {
            $table->id('id_servis');
            $table->string('nomor_servis', 20)->unique('uq_servis_nomor');
            $table->char('kode_tracking', 12)->unique('uq_servis_tracking');
            $table->foreignId('id_pelanggan')->constrained('pelanggan', 'id_pelanggan')->restrictOnDelete();
            $table->foreignId('id_teknisi')->nullable()->constrained('pengguna', 'id_pengguna')->restrictOnDelete();
            $table->enum('jenis_perangkat', ['hp', 'laptop', 'komputer', 'elektronik', 'lainnya']);
            $table->string('merek', 60);
            $table->string('model', 100);
            $table->string('nomor_seri', 100)->nullable();
            $table->text('keluhan');
            $table->text('kondisi_fisik')->nullable();
            $table->string('kelengkapan', 255)->nullable();
            $table->enum('status', [
                'diterima',
                'menunggu_diagnosis',
                'menunggu_persetujuan',
                'disetujui',
                'dikerjakan',
                'quality_control',
                'siap_diambil',
                'selesai',
                'ditolak',
                'dibatalkan',
            ])->default('diterima');
            $table->smallInteger('sla_hari_salinan');
            $table->decimal('biaya_pemeriksaan_salinan', 12, 2)->default(0);
            $table->dateTime('tanggal_masuk');
            $table->foreignId('dibuat_oleh')->constrained('pengguna', 'id_pengguna')->restrictOnDelete();
            $table->dateTime('dibuat_pada');
            $table->dateTime('diperbarui_pada');

            $table->index('status', 'idx_servis_status');
            $table->index(['id_teknisi', 'status'], 'idx_servis_teknisi');
            $table->index('id_pelanggan', 'idx_servis_pelanggan');
        });

        DB::statement('ALTER TABLE servis ADD CONSTRAINT chk_servis_biaya CHECK (biaya_pemeriksaan_salinan >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('servis');
    }
};
