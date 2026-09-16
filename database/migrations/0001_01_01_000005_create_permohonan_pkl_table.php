<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pemohon')->constrained('users');
            $table->foreignId('id_sub_instansi')->constrained('sub_instansi');
            $table->enum('jenis_pengajuan', ['individu', 'kelompok']);
            $table->enum('status', ['menunggu', 'diterima', 'ditolak', 'dibatalkan']);
            $table->string('sekolah', 255);
            $table->string('no_hp', 20);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('berkas_permohonan', 255);
            $table->text('catatan_admin')->nullable();
            $table->timestamps();

            // Dashboard Filter Indexes
            $table->index('status');
            $table->index('id_sub_instansi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan_pkl');
    }
};
