<?php

namespace Tests\Feature;

use App\Models\AngkatanPkpa;
use App\Models\HasilPrediksi;
use App\Models\PenilaianPkpa;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProjectInitializationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeder_creates_accounts_and_complete_cohorts_with_eight_percent_historical_noise(): void
    {
        $this->freezeTime();

        $this->seed();

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('angkatan_pkpa', 3);
        $this->assertDatabaseCount('peserta_pkpa', 250);
        $this->assertDatabaseCount('penilaian_pkpa', 250);
        $this->assertDatabaseCount('model_ml', 0);
        $this->assertDatabaseCount('hasil_prediksi', 0);

        $admin = User::where('email', 'admin@propindo.id')->firstOrFail();
        $manajemen = User::where('email', 'manajemen@propindo.id')->firstOrFail();
        $this->assertSame('admin_akademik', $admin->role);
        $this->assertSame('manajemen', $manajemen->role);
        $this->assertTrue(Hash::check('password123', $admin->password));
        $this->assertTrue(Hash::check('password123', $manajemen->password));

        foreach (['PKPA-X', 'PKPA-XI'] as $kode) {
            $angkatan = AngkatanPkpa::with('peserta.penilaian')->where('kode_angkatan', $kode)->firstOrFail();
            $penilaian = $angkatan->peserta->pluck('penilaian');
            $this->assertSame('selesai', $angkatan->status_angkatan);
            $this->assertCount(100, $penilaian);
            $this->assertSame(0, $penilaian->where('status_aktual', 'Belum Ditentukan')->count());

            $memenuhiKriteria = $penilaian->filter(fn (PenilaianPkpa $nilai): bool => $nilai->persen_kehadiran >= 80 && $nilai->nilai_hukum_acara >= 70 && $nilai->nilai_tugas_praktik >= 70
            );
            $diBawahKriteria = $penilaian->diff($memenuhiKriteria);
            $noise = $memenuhiKriteria->where('status_aktual', 'Tidak Lulus')->count()
                + $diBawahKriteria->where('status_aktual', 'Lulus')->count();

            $this->assertSame(8, $noise);
            $this->assertGreaterThan(0.5, $memenuhiKriteria->where('status_aktual', 'Lulus')->count() / $memenuhiKriteria->count());
            $this->assertGreaterThan(0.5, $diBawahKriteria->where('status_aktual', 'Tidak Lulus')->count() / $diBawahKriteria->count());
        }

        $aktif = AngkatanPkpa::with('peserta.penilaian')->where('kode_angkatan', 'PKPA-XII')->firstOrFail();
        $this->assertSame('berjalan', $aktif->status_angkatan);
        $this->assertCount(50, $aktif->peserta);
        $this->assertSame(50, $aktif->peserta->pluck('penilaian')->where('status_aktual', 'Belum Ditentukan')->count());

        foreach ([
            'persen_kehadiran', 'nilai_kode_etik', 'nilai_hukum_acara',
            'nilai_tugas_praktik', 'nilai_keaktifan', 'nilai_simulasi_upa',
        ] as $fitur) {
            $this->assertGreaterThan(10, $aktif->peserta->pluck('penilaian')->pluck($fitur)->unique()->count());
            $this->assertGreaterThanOrEqual(0, PenilaianPkpa::min($fitur));
            $this->assertLessThanOrEqual(100, PenilaianPkpa::max($fitur));
        }
    }

    public function test_reseeding_preserves_participant_and_assessment_identities_without_duplicates(): void
    {
        $this->freezeTime();
        $this->seed();
        $sebelum = PenilaianPkpa::orderBy('id')->get()->toArray();

        $this->seed();

        $this->assertSame($sebelum, PenilaianPkpa::orderBy('id')->get()->toArray());
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('angkatan_pkpa', 3);
        $this->assertDatabaseCount('peserta_pkpa', 250);
    }

    public function test_prediction_history_resolves_all_relationships_and_evaluation_json(): void
    {
        $this->travelTo(now()->startOfSecond());

        $prediksi = HasilPrediksi::factory()->create()->fresh();

        $this->assertTrue($prediksi->peserta->penilaian->is($prediksi->penilaian));
        $this->assertTrue($prediksi->penilaian->peserta->is($prediksi->peserta));
        $this->assertTrue($prediksi->peserta->angkatan->peserta->contains($prediksi->peserta));
        $this->assertTrue($prediksi->peserta->hasilPrediksi->contains($prediksi));
        $this->assertTrue($prediksi->penilaian->hasilPrediksi->contains($prediksi));
        $this->assertTrue($prediksi->modelMl->hasilPrediksi->contains($prediksi));
        $this->assertTrue($prediksi->prediktor->hasilPrediksi->contains($prediksi));
        $this->assertTrue($prediksi->modelMl->pembuat->modelMl->contains($prediksi->modelMl));
        $this->assertSame(['TP' => 18, 'TN' => 18, 'FP' => 2, 'FN' => 2], $prediksi->modelMl->confusion_matrix_json);
        $this->assertSame(0.25, $prediksi->modelMl->feature_importance_json['persen_kehadiran']);
        $this->assertFalse($prediksi->modelMl->is_active);
        $this->assertNull($prediksi->modelMl->max_depth);
        $this->assertSame('85.00', $prediksi->probabilitas_lulus);
        $this->assertTrue($prediksi->tanggal_prediksi->equalTo(now()));
    }
}
