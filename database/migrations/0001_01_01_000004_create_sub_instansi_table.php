<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_instansi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_instansi')->constrained('instansi')->cascadeOnDelete();
            $table->string('nama_sub_instansi', 255);
            $table->text('deskripsi')->nullable();
            $table->integer('batas_kuota')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_instansi');
    }
};
