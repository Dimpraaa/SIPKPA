<?php

namespace Database\Seeders;

use App\Models\AngkatanPkpa;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        fake()->seed(20261107);
        fake()->unique(true);

        DB::transaction(function (): void {
            $password = Hash::make('password123');

            foreach ([
                ['nama_lengkap' => 'Administrator Akademik Propindo', 'email' => 'admin@propindo.id', 'role' => 'admin_akademik'],
                ['nama_lengkap' => 'Manajemen Advokat Propindo', 'email' => 'manajemen@propindo.id', 'role' => 'manajemen'],
            ] as $akun) {
                User::updateOrCreate(['email' => $akun['email']], [...$akun, 'password' => $password]);
            }

            foreach ([
                ['kode' => 'X', 'mulai' => '2025-02-01', 'selesai' => '2025-04-27', 'status' => 'selesai', 'jumlah' => 100],
                ['kode' => 'XI', 'mulai' => '2025-08-02', 'selesai' => '2025-10-26', 'status' => 'selesai', 'jumlah' => 100],
                ['kode' => 'XII', 'mulai' => '2026-10-03', 'selesai' => '2026-12-20', 'status' => 'berjalan', 'jumlah' => 50],
            ] as $dataAngkatan) {
                $angkatan = AngkatanPkpa::updateOrCreate(
                    ['kode_angkatan' => 'PKPA-'.$dataAngkatan['kode']],
                    [
                        'nama_angkatan' => 'Angkatan '.$dataAngkatan['kode'],
                        'tanggal_mulai' => $dataAngkatan['mulai'],
                        'tanggal_selesai' => $dataAngkatan['selesai'],
                        'status_angkatan' => $dataAngkatan['status'],
                    ],
                );

                $pesertaDenganNoise = $dataAngkatan['status'] === 'selesai'
                    ? fake()->randomElements(range(1, $dataAngkatan['jumlah']), 8)
                    : [];

                for ($nomor = 1; $nomor <= $dataAngkatan['jumlah']; $nomor++) {
                    $nomorPeserta = sprintf('PROPINDO-%s-%03d', $dataAngkatan['kode'], $nomor);
                    $peserta = PesertaPkpa::updateOrCreate(
                        ['nomor_peserta' => $nomorPeserta],
                        PesertaPkpa::factory()->make([
                            'angkatan_id' => $angkatan->id,
                            'nomor_peserta' => $nomorPeserta,
                            'email' => strtolower($nomorPeserta).'@example.com',
                        ])->getAttributes(),
                    );

                    $penilaian = PenilaianPkpa::factory()->make(['peserta_id' => $peserta->id]);

                    if ($dataAngkatan['status'] === 'selesai') {
                        $memenuhiKriteria = $penilaian->persen_kehadiran >= 80
                            && $penilaian->nilai_hukum_acara >= 70
                            && $penilaian->nilai_tugas_praktik >= 70;

                        if (in_array($nomor, $pesertaDenganNoise, true)) {
                            $memenuhiKriteria = ! $memenuhiKriteria;
                        }

                        $penilaian->status_aktual = $memenuhiKriteria ? 'Lulus' : 'Tidak Lulus';
                    }

                    PenilaianPkpa::updateOrCreate(
                        ['peserta_id' => $peserta->id],
                        $penilaian->getAttributes(),
                    );
                }
            }
        });

        $this->command?->info('Data awal Propindo berhasil dibuat: 2 akun, 3 angkatan, 250 peserta dan penilaian (noise historis 8%).');
    }
}
