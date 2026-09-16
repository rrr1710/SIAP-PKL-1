<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota_permohonan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_permohonan')->constrained('permohonan_pkl')->cascadeOnDelete();
            $table->string('nama_mahasiswa', 255);
            $table->string('nim', 50);
            $table->string('sekolah', 255);
            $table->string('no_hp', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_permohonan');
    }
};
