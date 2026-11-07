<?php

namespace Database\Factories;

use App\Models\HasilPrediksi;
use App\Models\ModelMl;
use App\Models\PenilaianPkpa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HasilPrediksi>
 */
class HasilPrediksiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'penilaian_id' => PenilaianPkpa::factory(),
            'peserta_id' => fn (array $attributes): int => PenilaianPkpa::findOrFail($attributes['penilaian_id'])->peserta_id,
            'model_id' => ModelMl::factory(),
            'diprediksi_oleh' => User::factory(),
            'status_prediksi' => 'Lulus',
            'probabilitas_lulus' => 85,
            'probabilitas_gagal' => 15,
            'rekomendasi_tindak_lanjut' => 'Pertahankan kehadiran dan lanjutkan latihan simulasi UPA.',
            'tanggal_prediksi' => now(),
        ];
    }
}
