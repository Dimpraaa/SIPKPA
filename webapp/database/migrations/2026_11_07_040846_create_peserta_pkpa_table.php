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
        Schema::create('peserta_pkpa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('angkatan_id')->constrained('angkatan_pkpa')->restrictOnDelete();
            $table->string('nomor_peserta')->unique();
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('email');
            $table->string('no_hp', 20);
            $table->string('asal_kampus_s1');
            $table->decimal('ipk_s1', 3, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_pkpa');
    }
};
