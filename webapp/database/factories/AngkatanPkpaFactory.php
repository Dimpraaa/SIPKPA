<?php

namespace Database\Factories;

use App\Models\AngkatanPkpa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AngkatanPkpa>
 */
class AngkatanPkpaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_angkatan' => fake()->unique()->bothify('PKPA-####-??'),
            'nama_angkatan' => 'Angkatan PKPA '.fake()->numberBetween(13, 99),
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-12-20',
            'status_angkatan' => 'berjalan',
        ];
    }
}
