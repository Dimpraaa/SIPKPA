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
        Schema::create('penilaian_pkpa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->unique()->constrained('peserta_pkpa')->restrictOnDelete();
            $table->decimal('persen_kehadiran', 5, 2);
            $table->decimal('nilai_kode_etik', 5, 2);
            $table->decimal('nilai_hukum_acara', 5, 2);
            $table->decimal('nilai_tugas_praktik', 5, 2);
            $table->decimal('nilai_keaktifan', 5, 2);
            $table->decimal('nilai_simulasi_upa', 5, 2);
            $table->decimal('nilai_akhir_rata', 5, 2);
            $table->enum('status_aktual', ['Lulus', 'Tidak Lulus', 'Belum Ditentukan'])
                ->default('Belum Ditentukan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian_pkpa');
    }
};
