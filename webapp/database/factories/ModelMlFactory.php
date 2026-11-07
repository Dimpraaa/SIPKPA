<?php

namespace Database\Factories;

use App\Models\ModelMl;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModelMl>
 */
class ModelMlFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dibuat_oleh' => User::factory(),
            'nama_versi' => 'RF-Uji-'.fake()->unique()->numerify('####'),
            'jumlah_data_latih' => 160,
            'jumlah_data_uji' => 40,
            'n_estimators' => 100,
            'max_depth' => null,
            'criterion' => 'gini',
            'akurasi' => 90,
            'presisi' => 90,
            'recall' => 90,
            'f1_score' => 90,
            'confusion_matrix_json' => ['TP' => 18, 'TN' => 18, 'FP' => 2, 'FN' => 2],
            'feature_importance_json' => [
                'persen_kehadiran' => 0.25,
                'nilai_kode_etik' => 0.10,
                'nilai_hukum_acara' => 0.25,
                'nilai_tugas_praktik' => 0.20,
                'nilai_keaktifan' => 0.08,
                'nilai_simulasi_upa' => 0.12,
            ],
            'file_path_model' => 'models/rf-uji.joblib',
            'is_active' => false,
        ];
    }
}
