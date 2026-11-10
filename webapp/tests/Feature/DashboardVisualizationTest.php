<?php

namespace Tests\Feature;

use App\Models\AngkatanPkpa;
use App\Models\HasilPrediksi;
use App\Models\ModelMl;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardVisualizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_separates_actual_status_and_academic_averages_from_latest_predictions(): void
    {
        $fixture = $this->dashboardFixture();
        $this->actingAs($fixture['user']);
        Http::preventStrayRequests();
        Http::fake();

        $response = $this->get(route('dashboard'));

        $response->assertOk()->assertSee('Dashboard')->assertSee('Rata-rata nilai akhir per angkatan')
            ->assertSee('Distribusi status kelulusan aktual')->assertSee('Prediksi model tersimpan')
            ->assertSee('Kontribusi relatif fitur model')->assertSee('Confusion matrix')
            ->assertSee('pengujian data simulasi model aktif, bukan tingkat kelulusan peserta')->assertDontSee('LUA-');
        $this->assertSame([
            'peserta' => 5, 'lulus' => 2, 'berisiko' => 1, 'terprediksi' => 3, 'belum_prediksi' => 2, 'cakupan' => 60.0,
        ], $response->viewData('ringkasan'));
        $this->assertSame([
            'dinilai' => 4, 'belum_dinilai' => 1, 'status_ditentukan' => 3,
            'lulus' => 1, 'tidak_lulus' => 2, 'belum_ditentukan' => 1,
        ], $response->viewData('akademik'));
        $chartData = $this->chartData($response);
        $this->assertSame(['akademik', 'aktual', 'prediksi'], array_keys($chartData));
        $this->assertSame(['Angkatan X', 'Angkatan XI', 'Angkatan XII'], $chartData['akademik']['labels']);
        $this->assertSame([42, 0, 75], $chartData['akademik']['values']);
        $this->assertSame([1, 2, 1, 1], $chartData['aktual']['values']);
        $this->assertSame([2, 1], $chartData['prediksi']['values']);
        $this->assertSame([2, 1, 1], $chartData['akademik']['counts']);
        $this->assertSame([50.0, 0.0, null], array_column($response->viewData('tren'), 'persentase_kelulusan'));
        $this->assertSame([90.0, 0.0, 75.0], array_column(array_column($response->viewData('tren'), 'rata_rata'), 'persen_kehadiran'));
        $this->assertSame([30.0, 5.0, 25.0, 20.0, 10.0, 10.0], array_column($response->viewData('featureImportance'), 'persentase'));
        $this->assertSame($fixture['model']->id, $response->viewData('modelAktif')->id);
        $response->assertSee('data-confusion="TP">20', false)->assertSee('data-confusion="TN">15', false)
            ->assertSee('data-confusion="FP">2', false)->assertSee('data-confusion="FN">3', false)
            ->assertSee('87,50%')->assertSee('90,91%')->assertSee('86,96%')->assertSee('88,89%');
        $this->assertCount(3, $response->viewData('riwayat'));
        $this->assertDatabaseCount('hasil_prediksi', 5);
        Http::assertNothingSent();
    }

    public function test_cohort_filter_scopes_both_visualizations_summaries_history_and_navigation(): void
    {
        $fixture = $this->dashboardFixture();
        $this->actingAs($fixture['user']);

        $response = $this->get(route('dashboard', ['angkatan_id' => $fixture['x']->id]));

        $response->assertOk();
        $this->assertSame([
            'peserta' => 3, 'lulus' => 1, 'berisiko' => 1, 'terprediksi' => 2, 'belum_prediksi' => 1, 'cakupan' => 66.67,
        ], $response->viewData('ringkasan'));
        $chartData = $this->chartData($response);
        $this->assertSame([1, 1, 0, 1], $chartData['aktual']['values']);
        $this->assertSame(['Angkatan X'], $chartData['akademik']['labels']);
        $this->assertSame([42], $chartData['akademik']['values']);
        $this->assertSame(2, $response->viewData('akademik')['dinilai']);
        $response->assertSee(route('peserta.index', ['angkatan_id' => $fixture['x']->id]))
            ->assertSee(route('prediksi.index', ['angkatan_id' => $fixture['x']->id]))
            ->assertSee(route('laporan.berisiko', ['angkatan_id' => $fixture['x']->id]));
        $this->assertSame($fixture['model']->id, $response->viewData('modelAktif')->id);
        $this->assertCount(2, $response->viewData('riwayat'));
        $response = $this->get(route('dashboard', ['angkatan_id' => $fixture['xii']->id]));
        $response->assertOk()->assertSee('Belum ada prediksi tersimpan untuk angkatan yang dipilih.')
            ->assertSee('id="aktual-chart"', false)->assertSee('id="akademik-chart"', false);
        $this->assertSame([0, 0, 1, 0], $this->chartData($response)['aktual']['values']);
        $this->assertSame(['Angkatan XII'], $this->chartData($response)['akademik']['labels']);
        $this->assertSame(0.0, $response->viewData('ringkasan')['cakupan']);
    }

    public function test_dashboard_handles_empty_database_and_missing_active_model_without_fake_metrics(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manajemen']));

        $response = $this->get(route('dashboard'));

        $response->assertOk()->assertSee('Belum ada model aktif')->assertSee('Belum ada penilaian')->assertSee('Belum ada angkatan berjalan')
            ->assertDontSee('id="akademik-chart"', false)->assertDontSee('id="aktual-chart"', false);
        $this->assertNull($response->viewData('ringkasan')['cakupan']);
        $this->assertSame([0, 0, 0, 0], $this->chartData($response)['aktual']['values']);
        $this->assertSame([], $this->chartData($response)['akademik']['labels']);
        $this->assertNull($response->viewData('modelAktif'));

        $angkatan = AngkatanPkpa::factory()->create();
        $response = $this->get(route('dashboard', ['angkatan_id' => $angkatan->id]));
        $response->assertOk();
        $this->assertSame([null], $this->chartData($response)['akademik']['values']);
        $this->assertSame([0, 0, 0, 0], $this->chartData($response)['aktual']['values']);
    }

    public function test_dashboard_requires_authentication_validates_filter_and_serializes_chart_labels_safely(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'manajemen']));
        $this->getJson(route('dashboard', ['angkatan_id' => '1 OR 1=1']))->assertUnprocessable()->assertJsonValidationErrors('angkatan_id');
        $this->getJson(route('dashboard', ['angkatan_id' => 99999]))->assertUnprocessable()->assertJsonValidationErrors('angkatan_id');
        $name = '</script><img src=x onerror=alert(1)>';
        $angkatan = AngkatanPkpa::factory()->create(['nama_angkatan' => $name]);

        $response = $this->get(route('dashboard'));

        $response->assertOk()->assertDontSee($name, false);
        $this->assertSame([$name], $this->chartData($response)['akademik']['labels']);
    }

    public function test_unassessed_participant_is_not_represented_as_a_zero_academic_score(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manajemen']));
        $peserta = PesertaPkpa::factory()->create();

        $response = $this->get(route('dashboard', ['angkatan_id' => $peserta->angkatan_id]));

        $response->assertOk()->assertSee('Belum ada penilaian')->assertDontSee('id="akademik-chart"', false)
            ->assertSee('id="aktual-chart"', false);
        $this->assertSame([null], $this->chartData($response)['akademik']['values']);
        $this->assertSame([0, 0, 0, 1], $this->chartData($response)['aktual']['values']);
        $this->assertSame(1, $response->viewData('akademik')['belum_dinilai']);
        $this->assertSame(0, $response->viewData('akademik')['belum_ditentukan']);
    }

    public function test_academic_average_includes_zero_and_excludes_participants_without_assessment(): void
    {
        $this->actingAs(User::factory()->create());
        $angkatan = AngkatanPkpa::factory()->create();
        $this->assessment($angkatan, [0, 0, 0, 0, 0, 0], 'Belum Ditentukan');
        $this->assessment($angkatan, [100, 100, 100, 100, 100, 100], 'Belum Ditentukan');
        PesertaPkpa::factory()->for($angkatan, 'angkatan')->create();

        $response = $this->get(route('dashboard', ['angkatan_id' => $angkatan->id]));

        $response->assertOk();
        $this->assertSame([50], $this->chartData($response)['akademik']['values']);
        $this->assertSame([0, 0, 2, 1], $this->chartData($response)['aktual']['values']);
        $this->assertSame(2, $response->viewData('akademik')['dinilai']);
        $this->assertSame(0, $response->viewData('akademik')['status_ditentukan']);
    }

    public function test_layout_shows_latest_running_cohort_and_updates_when_it_finishes(): void
    {
        $this->actingAs(User::factory()->create());
        AngkatanPkpa::factory()->create(['nama_angkatan' => 'Angkatan Lama', 'tanggal_mulai' => '2026-01-01']);
        AngkatanPkpa::factory()->create(['nama_angkatan' => 'Angkatan Berjalan', 'tanggal_mulai' => '2026-10-01']);
        $latest = AngkatanPkpa::factory()->create(['nama_angkatan' => 'Angkatan Berjalan Terbaru', 'tanggal_mulai' => '2026-10-01']);
        AngkatanPkpa::factory()->create(['nama_angkatan' => 'Angkatan Selesai', 'tanggal_mulai' => '2026-11-01', 'status_angkatan' => 'selesai']);

        $response = $this->get(route('peserta.index'));

        $response->assertOk()->assertSee('title="Angkatan Berjalan Terbaru"', false)
            ->assertDontSee('title="Angkatan Selesai"', false);

        $latest->update(['status_angkatan' => 'selesai']);
        $this->get(route('angkatan.index'))->assertOk()->assertSee('title="Angkatan Berjalan"', false);
    }

    /** @return array<string, mixed> */
    private function dashboardFixture(): array
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create();
        $model = ModelMl::factory()->for($user, 'pembuat')->create([
            'nama_versi' => 'RF-Aktif', 'is_active' => true, 'akurasi' => 87.5, 'presisi' => 90.91, 'recall' => 86.96, 'f1_score' => 88.89,
            'confusion_matrix_json' => ['TP' => 20, 'TN' => 15, 'FP' => 2, 'FN' => 3],
            'feature_importance_json' => array_combine(array_keys(PenilaianPkpa::FEATURE_LABELS), [0.3, 0.05, 0.25, 0.2, 0.1, 0.1]),
        ]);
        ModelMl::factory()->for($user, 'pembuat')->create(['is_active' => false, 'akurasi' => 99, 'nama_versi' => 'RF-Tidak-Aktif']);
        $xii = AngkatanPkpa::factory()->create(['nama_angkatan' => 'Angkatan XII', 'tanggal_mulai' => '2026-10-01']);
        $xi = AngkatanPkpa::factory()->create(['nama_angkatan' => 'Angkatan XI', 'tanggal_mulai' => '2025-06-01', 'status_angkatan' => 'selesai']);
        $x = AngkatanPkpa::factory()->create(['nama_angkatan' => 'Angkatan X', 'tanggal_mulai' => '2025-01-01', 'status_angkatan' => 'selesai']);
        $first = $this->assessment($x, [100, 80, 60, 40, 20, 0], 'Lulus');
        $second = $this->assessment($x, [80, 60, 40, 20, 0, 100], 'Tidak Lulus');
        PesertaPkpa::factory()->for($x, 'angkatan')->create();
        $third = $this->assessment($xi, [0, 0, 0, 0, 0, 0], 'Tidak Lulus');
        $this->assessment($xii, [75, 75, 75, 75, 75, 75], 'Belum Ditentukan');
        foreach ([
            [$first, 'Berisiko Tidak Lulus', '2026-11-07 10:00:00'],
            [$first, 'Lulus', '2026-11-06 10:00:00'],
            [$second, 'Berisiko Tidak Lulus', '2026-11-07 10:00:00'],
            [$second, 'Lulus', '2026-11-07 10:00:00'],
            [$third, 'Lulus', '2026-11-07 10:00:00'],
        ] as [$nilai, $status, $date]) {
            HasilPrediksi::factory()->for($nilai, 'penilaian')->for($model, 'modelMl')->for($user, 'prediktor')
                ->create(['status_prediksi' => $status, 'tanggal_prediksi' => $date]);
        }

        return compact('user', 'model', 'x', 'xi', 'xii');
    }

    /** @param list<int> $values */
    private function assessment(AngkatanPkpa $angkatan, array $values, string $status): PenilaianPkpa
    {
        $peserta = PesertaPkpa::factory()->for($angkatan, 'angkatan')->create();

        return PenilaianPkpa::factory()->for($peserta, 'peserta')->create([
            ...array_combine(array_keys(PenilaianPkpa::FEATURE_LABELS), $values), 'status_aktual' => $status,
        ]);
    }

    /** @return array<string, mixed> */
    private function chartData(TestResponse $response): array
    {
        $matched = preg_match('/<script id="dashboard-chart-data" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $this->assertSame(1, $matched);

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
