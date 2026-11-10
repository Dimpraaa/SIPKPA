<?php

namespace Tests\Feature;

use App\Models\AngkatanPkpa;
use App\Models\ModelMl;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RandomForestWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_training_sends_only_labeled_features_persists_metrics_and_activates_exactly_one_model(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        PenilaianPkpa::factory()->count(10)->sequence(fn ($sequence) => ['status_aktual' => $sequence->index < 6 ? 'Lulus' : 'Tidak Lulus'])->create();
        PenilaianPkpa::factory()->create(['status_aktual' => 'Belum Ditentukan']);
        $previous = ModelMl::factory()->for($user, 'pembuat')->create(['is_active' => true, 'criterion' => 'entropy']);
        Http::preventStrayRequests();
        Http::fake([config('services.ml.url').'/api/train' => Http::response($this->trainingResponse())]);

        $this->actingAs($user)->post(route('model.store'), $this->parameters())->assertSessionHasNoErrors()->assertRedirect(route('model.index'));

        Http::assertSent(function (Request $request): bool {
            $rows = $request['data'];

            return $request->url() === config('services.ml.url').'/api/train' && count($rows) === 10
                && count($rows[0]) === 7 && ! array_key_exists('nilai_akhir_rata', $rows[0])
                && is_float($rows[0]['persen_kehadiran']) && $request['max_depth'] === null
                && $request['n_estimators'] === 100 && $request['criterion'] === 'gini'
                && $request['test_size'] === 0.2 && $request['random_state'] === 42;
        });
        $model = ModelMl::latest('id')->firstOrFail();
        $this->assertSame($user->id, $model->dibuat_oleh);
        $this->assertSame('RF-Eksperimen', $model->nama_versi);
        $this->assertSame('gini', $model->criterion);
        $this->assertSame('87.50', $model->akurasi);
        $this->assertSame('90.00', $model->presisi);
        $this->assertSame('80.00', $model->recall);
        $this->assertSame('84.71', $model->f1_score);
        $this->assertSame(['TP' => 1, 'TN' => 1, 'FP' => 0, 'FN' => 0], $model->confusion_matrix_json);
        $this->assertFalse($model->is_active);
        $this->patch(route('model.activate', $model))->assertRedirect(route('model.index'));
        $this->assertTrue($model->fresh()->is_active);
        $this->assertFalse($previous->fresh()->is_active);
        $this->assertSame('entropy', $previous->fresh()->criterion);
        $this->assertSame(1, ModelMl::where('is_active', true)->count());
        $this->get(route('model.index'))->assertOk()->assertSee('RF-Eksperimen')->assertSee('87,50%')->assertSee('entropy')
            ->assertDontSee('name="criterion"', false)
            ->assertSee('name="n_estimators"', false)->assertSee('name="max_depth"', false);
    }

    public function test_new_experiments_always_use_gini_even_when_a_client_submits_entropy(): void
    {
        $user = User::factory()->create();
        PenilaianPkpa::factory()->count(10)->sequence(['status_aktual' => 'Lulus'], ['status_aktual' => 'Tidak Lulus'])->create();
        Http::preventStrayRequests();
        Http::fake([config('services.ml.url').'/api/train' => Http::response($this->trainingResponse())]);

        $this->actingAs($user)->post(route('model.store'), [
            ...$this->parameters(), 'n_estimators' => 250, 'max_depth' => 6, 'criterion' => 'entropy',
        ])->assertSessionHasNoErrors()->assertRedirect(route('model.index'));

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.ml.url').'/api/train'
            && $request['criterion'] === 'gini' && $request['n_estimators'] === 250 && $request['max_depth'] === 6);
        $this->assertDatabaseCount('model_ml', 1);
        $this->assertDatabaseHas('model_ml', [
            'nama_versi' => 'RF-Eksperimen', 'criterion' => 'gini', 'n_estimators' => 250, 'max_depth' => 6,
        ]);
    }

    public function test_connection_failure_and_rejected_or_malformed_responses_do_not_create_models(): void
    {
        $this->actingAs(User::factory()->create());
        PenilaianPkpa::factory()->count(10)->sequence(['status_aktual' => 'Lulus'], ['status_aktual' => 'Tidak Lulus'])->create();
        Http::preventStrayRequests();
        Http::fake([config('services.ml.url').'/api/train' => Http::failedConnection()]);

        $this->post(route('model.store'), $this->parameters())->assertSessionHasErrors('ml');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('model_ml', 0);
        foreach ([Http::response(['detail' => 'Data tidak cukup.'], 422), Http::response(['akurasi' => 100]), Http::response('bukan JSON', 200)] as $response) {
            Http::fake([config('services.ml.url').'/api/train' => $response]);
            $this->post(route('model.store'), $this->parameters())->assertSessionHasErrors('ml');
            $this->assertDatabaseCount('model_ml', 0);
        }
    }

    public function test_training_requires_both_historical_classes_and_valid_parameters(): void
    {
        $this->actingAs(User::factory()->create());
        PenilaianPkpa::factory()->count(10)->create(['status_aktual' => 'Lulus']);
        Http::preventStrayRequests();
        Http::fake();

        $this->post(route('model.store'), $this->parameters())->assertSessionHasErrors('ml');
        foreach ([
            ['n_estimators' => 0, 'max_depth' => 0],
            ['n_estimators' => 2001, 'max_depth' => 101],
            ['n_estimators' => 1.5, 'max_depth' => 1.5],
        ] as $parameters) {
            $this->post(route('model.store'), [...$this->parameters(), ...$parameters])
                ->assertSessionHasErrors(['n_estimators', 'max_depth']);
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('model_ml', 0);
    }

    public function test_individual_and_batch_prediction_use_active_model_preserve_order_and_store_history(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $angkatan = AngkatanPkpa::factory()->create();
        $first = PesertaPkpa::factory()->for($angkatan, 'angkatan')->create();
        $second = PesertaPkpa::factory()->for($angkatan, 'angkatan')->create();
        PenilaianPkpa::factory()->for($first, 'peserta')->create(['persen_kehadiran' => 95]);
        PenilaianPkpa::factory()->for($second, 'peserta')->create(['persen_kehadiran' => 50]);
        $model = ModelMl::factory()->for($user, 'pembuat')->create(['is_active' => true]);
        $passed = ['status_prediksi' => 'Lulus', 'probabilitas_lulus' => 90, 'probabilitas_gagal' => 10, 'rekomendasi_tindak_lanjut' => 'Pertahankan performa dan siap mengikuti UPA'];
        $risk = ['status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 20, 'probabilitas_gagal' => 80, 'rekomendasi_tindak_lanjut' => 'Ikuti pembinaan disiplin kehadiran.'];
        Http::preventStrayRequests();
        Http::fake([config('services.ml.url').'/api/predict' => fn (Request $request) => Http::response([
            'file_path_model' => $model->file_path_model,
            'hasil_prediksi' => count($request['data']) === 1 ? [$passed] : [$passed, $risk],
        ])]);

        $this->actingAs($user)->post(route('prediksi.individual', $first))->assertSessionHasNoErrors()->assertRedirect(route('peserta.show', $first));
        $this->post(route('prediksi.angkatan', $angkatan))->assertSessionHasNoErrors()->assertRedirect(route('prediksi.index', ['angkatan_id' => $angkatan->id]));

        Http::assertSent(fn (Request $request): bool => $request['file_path_model'] === $model->file_path_model && count($request['data']) === 2 && $request['data'][0]['persen_kehadiran'] === 95.0 && $request['data'][1]['persen_kehadiran'] === 50.0);
        $this->assertDatabaseCount('hasil_prediksi', 3);
        $this->assertDatabaseHas('hasil_prediksi', ['peserta_id' => $second->id, 'penilaian_id' => $second->penilaian->id, 'model_id' => $model->id, 'diprediksi_oleh' => $user->id, 'status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_gagal' => 80]);
        $this->get(route('prediksi.index'))->assertOk()->assertSee('Ikuti pembinaan disiplin kehadiran.');
        $this->get(route('peserta.show', $second))->assertOk()->assertSee('Berisiko Tidak Lulus');
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_prediction_preconditions_block_network_calls_for_missing_model_or_assessments_and_finished_cohorts(): void
    {
        $user = User::factory()->create();
        $peserta = PesertaPkpa::factory()->create();
        Http::preventStrayRequests();
        Http::fake();
        $this->actingAs($user)->post(route('prediksi.individual', $peserta))->assertSessionHasErrors('ml');
        ModelMl::factory()->create(['is_active' => true]);
        $this->post(route('prediksi.individual', $peserta))->assertSessionHasErrors('peserta');
        $peserta->angkatan->update(['status_angkatan' => 'selesai']);
        $this->post(route('prediksi.angkatan', $peserta->angkatan))->assertSessionHasErrors('angkatan');
        Http::assertNothingSent();
        $this->assertDatabaseCount('hasil_prediksi', 0);
    }

    public function test_incomplete_prediction_response_rolls_back_the_whole_batch(): void
    {
        $user = User::factory()->create();
        $angkatan = AngkatanPkpa::factory()->create();
        $peserta = PesertaPkpa::factory()->count(2)->for($angkatan, 'angkatan')->create();
        foreach ($peserta as $row) {
            PenilaianPkpa::factory()->for($row, 'peserta')->create();
        }
        $model = ModelMl::factory()->create(['is_active' => true]);
        Http::preventStrayRequests();
        Http::fake([config('services.ml.url').'/api/predict' => Http::response(['file_path_model' => $model->file_path_model, 'hasil_prediksi' => []])]);

        $this->actingAs($user)->post(route('prediksi.angkatan', $angkatan))->assertSessionHasErrors('ml');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('hasil_prediksi', 0);
    }

    /** @return array<string, mixed> */
    private function parameters(): array
    {
        return ['nama_versi' => 'RF-Eksperimen', 'n_estimators' => 100, 'max_depth' => null];
    }

    /** @return array<string, mixed> */
    private function trainingResponse(): array
    {
        return [
            'jumlah_data_latih' => 8, 'jumlah_data_uji' => 2,
            'akurasi' => 87.5, 'presisi' => 90, 'recall' => 80, 'f1_score' => 84.71,
            'confusion_matrix' => ['TP' => 1, 'TN' => 1, 'FP' => 0, 'FN' => 0],
            'feature_importance' => array_fill_keys(array_keys(PenilaianPkpa::FEATURE_LABELS), 1 / 6),
            'file_path_model' => 'models/rf_model_20261107_120000_000001.pkl',
        ];
    }
}
