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
        Schema::create('hasil_prediksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta_pkpa')->restrictOnDelete();
            $table->foreignId('penilaian_id')->constrained('penilaian_pkpa')->restrictOnDelete();
            $table->foreignId('model_id')->constrained('model_ml')->restrictOnDelete();
            $table->foreignId('diprediksi_oleh')->constrained('users')->restrictOnDelete();
            $table->enum('status_prediksi', ['Lulus', 'Berisiko Tidak Lulus']);
            $table->decimal('probabilitas_lulus', 5, 2);
            $table->decimal('probabilitas_gagal', 5, 2);
            $table->text('rekomendasi_tindak_lanjut');
            $table->dateTime('tanggal_prediksi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_prediksi');
    }
};
