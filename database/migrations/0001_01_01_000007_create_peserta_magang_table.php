<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peserta_magang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_sub_instansi')->constrained('sub_instansi');
            $table->foreignId('id_permohonan')->nullable()->constrained('permohonan_pkl')->nullOnDelete();
            $table->string('nama_peserta', 255);
            $table->string('nim', 50);
            $table->string('sekolah', 255);
            $table->string('no_hp', 20);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status_magang', ['aktif', 'selesai', 'dibatalkan']);
            $table->timestamps();

            // Composite Index for Dynamic Quota Calculation
            $table->index(['id_sub_instansi', 'status_magang', 'tanggal_mulai', 'tanggal_selesai'], 'idx_kuota_peserta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peserta_magang');
    }
};
