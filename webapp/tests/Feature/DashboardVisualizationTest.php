<?php

namespace Tests\Feature;

use App\Models\AngkatanPkpa;
use App\Models\HasilPrediksi;
use App\Models\ModelMl;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardVisualizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_uses_latest_prediction_per_participant_and_real_aggregates_for_all_four_outputs(): void
    {
        $fixture = $this->dashboardFixture();
        $this->actingAs($fixture['user']);

        $response = $this->get(route('dashboard'));

        $response->assertOk()->assertSee('LUA-01')->assertSee('LUA-02')->assertSee('LUA-03')->assertSee('LUA-04');
        $this->assertSame([
            'peserta' => 5, 'lulus' => 2, 'berisiko' => 1, 'terprediksi' => 3, 'belum_prediksi' => 2, 'cakupan' => 60.0,
        ], $response->viewData('ringkasan'));
        $chartData = $this->chartData($response);
        $this->assertSame([2, 1], $chartData['proporsi']['values']);
        $this->assertSame(['Angkatan X', 'Angkatan XI', 'Angkatan XII'], $chartData['tren']['labels']);
        $this->assertSame([50, 0, null], $chartData['tren']['kelulusan']);
        $this->assertSame([90, 0, 75], $chartData['tren']['komponen'][0]['values']);
        $this->assertSame([70, 0, 75], $chartData['tren']['komponen'][1]['values']);
        $this->assertSame([50, 0, 75], $chartData['tren']['komponen'][2]['values']);
        $this->assertSame([30, 0, 75], $chartData['tren']['komponen'][3]['values']);
        $this->assertSame([10, 0, 75], $chartData['tren']['komponen'][4]['values']);
        $this->assertSame([50, 0, 75], $chartData['tren']['komponen'][5]['values']);
        $this->assertSame([30, 5, 25, 20, 10, 10], array_column($chartData['featureImportance'], 'persentase'));
        $this->assertSame($fixture['model']->id, $response->viewData('modelAktif')->id);
        $response->assertSee('data-confusion="TP">20', false)->assertSee('data-confusion="TN">15', false)
            ->assertSee('data-confusion="FP">2', false)->assertSee('data-confusion="FN">3', false)
            ->assertSee('value="87.5" max="100"', false)->assertSee('value="90.91" max="100"', false)
            ->assertSee('value="86.96" max="100"', false)->assertSee('value="88.89" max="100"', false);
        $this->assertCount(3, $response->viewData('riwayat'));
    }

    public function test_cohort_filter_changes_summary_and_donut_but_keeps_comparative_trends_and_active_model(): void
    {
        $fixture = $this->dashboardFixture();
        $this->actingAs($fixture['user']);

        $response = $this->get(route('dashboard', ['angkatan_id' => $fixture['x']->id]));

        $response->assertOk();
        $this->assertSame([
            'peserta' => 3, 'lulus' => 1, 'berisiko' => 1, 'terprediksi' => 2, 'belum_prediksi' => 1, 'cakupan' => 66.67,
        ], $response->viewData('ringkasan'));
        $chartData = $this->chartData($response);
        $this->assertSame([1, 1], $chartData['proporsi']['values']);
        $this->assertSame(['Angkatan X', 'Angkatan XI', 'Angkatan XII'], $chartData['tren']['labels']);
        $this->assertSame($fixture['model']->id, $response->viewData('modelAktif')->id);
        $this->assertCount(2, $response->viewData('riwayat'));
        $response = $this->get(route('dashboard', ['angkatan_id' => $fixture['xii']->id]));
        $response->assertOk()->assertSee('Belum ada prediksi untuk angkatan ini.')->assertDontSee('id="proporsi-chart"', false);
        $this->assertSame([0, 0], $this->chartData($response)['proporsi']['values']);
        $this->assertSame(0.0, $response->viewData('ringkasan')['cakupan']);
    }

    public function test_dashboard_handles_empty_database_and_missing_active_model_without_fake_metrics(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manajemen']));

        $response = $this->get(route('dashboard'));

        $response->assertOk()->assertSee('Belum ada model aktif')->assertSee('Belum ada penilaian')
            ->assertDontSee('id="proporsi-chart"', false)->assertDontSee('id="importance-chart"', false)->assertDontSee('id="tren-chart"', false);
        $this->assertNull($response->viewData('ringkasan')['cakupan']);
        $this->assertSame([], $this->chartData($response)['featureImportance']);
        $this->assertSame([], $this->chartData($response)['tren']['labels']);
        $this->assertNull($response->viewData('modelAktif'));

        $angkatan = AngkatanPkpa::factory()->create();
        $response = $this->get(route('dashboard', ['angkatan_id' => $angkatan->id]));
        $response->assertOk();
        $this->assertSame([null], $this->chartData($response)['tren']['kelulusan']);
        $this->assertSame([null], $this->chartData($response)['tren']['komponen'][0]['values']);
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
        $this->assertSame([$name], $this->chartData($response)['tren']['labels']);
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
