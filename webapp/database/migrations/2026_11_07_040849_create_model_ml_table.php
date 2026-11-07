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
        Schema::create('model_ml', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->string('nama_versi');
            $table->unsignedInteger('jumlah_data_latih');
            $table->unsignedInteger('jumlah_data_uji');
            $table->unsignedInteger('n_estimators');
            $table->unsignedInteger('max_depth')->nullable();
            $table->enum('criterion', ['gini', 'entropy']);
            $table->decimal('akurasi', 5, 2);
            $table->decimal('presisi', 5, 2);
            $table->decimal('recall', 5, 2);
            $table->decimal('f1_score', 5, 2);
            $table->json('confusion_matrix_json');
            $table->json('feature_importance_json');
            $table->string('file_path_model');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_ml');
    }
};
