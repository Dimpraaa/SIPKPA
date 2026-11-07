<?php

namespace Database\Factories;

use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenilaianPkpa>
 */
class PenilaianPkpaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kemampuanDasar = fake()->randomFloat(2, 58, 92);
        $nilai = [
            'nilai_kode_etik' => $this->variedScore($kemampuanDasar, 13),
            'nilai_hukum_acara' => $this->variedScore($kemampuanDasar, 12),
            'nilai_tugas_praktik' => $this->variedScore($kemampuanDasar, 12),
            'nilai_keaktifan' => $this->variedScore($kemampuanDasar, 18),
            'nilai_simulasi_upa' => $this->variedScore($kemampuanDasar - 3, 15),
        ];

        return [
            'peserta_id' => PesertaPkpa::factory(),
            'persen_kehadiran' => $this->variedScore($kemampuanDasar + 12, 12),
            ...$nilai,
            'nilai_akhir_rata' => round(array_sum($nilai) / count($nilai), 2),
            'status_aktual' => 'Belum Ditentukan',
        ];
    }

    private function variedScore(float $base, int $variation): float
    {
        return round(max(0, min(100, $base + fake()->randomFloat(2, -$variation, $variation))), 2);
    }
}
