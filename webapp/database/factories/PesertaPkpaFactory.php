<?php

namespace Database\Factories;

use App\Models\AngkatanPkpa;
use App\Models\PesertaPkpa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PesertaPkpa>
 */
class PesertaPkpaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenisKelamin = fake()->randomElement(['L', 'P']);

        return [
            'angkatan_id' => AngkatanPkpa::factory(),
            'nomor_peserta' => fake()->unique()->numerify('PKPA-########'),
            'nama_lengkap' => fake()->firstName($jenisKelamin === 'L' ? 'male' : 'female').' '.fake()->lastName(),
            'jenis_kelamin' => $jenisKelamin,
            'email' => fake()->unique()->safeEmail(),
            'no_hp' => fake()->numerify('08##########'),
            'asal_kampus_s1' => fake()->randomElement([
                'Universitas Indonesia', 'Universitas Gadjah Mada', 'Universitas Padjadjaran',
                'Universitas Diponegoro', 'Universitas Airlangga', 'Universitas Brawijaya',
                'Universitas Trisakti', 'Universitas Pancasila', 'Universitas Tarumanagara',
                'Universitas Islam Indonesia', 'Universitas Hasanuddin', 'Universitas Sumatera Utara',
            ]),
            'ipk_s1' => fake()->randomFloat(2, 2.65, 3.95),
        ];
    }
}
