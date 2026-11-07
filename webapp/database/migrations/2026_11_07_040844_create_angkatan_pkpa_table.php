<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('angkatan_pkpa', function (Blueprint $table) {
            $table->id();
            $table->string('kode_angkatan')->unique();
            $table->string('nama_angkatan');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status_angkatan', ['berjalan', 'selesai']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('angkatan_pkpa');
    }
};
