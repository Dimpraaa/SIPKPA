<?php

namespace Tests\Feature;

use App\Models\AngkatanPkpa;
use App\Models\HasilPrediksi;
use App\Models\ModelMl;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PrediksiIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{string}> */
    public static function searches(): array
    {
        return ['nama' => ['Nama Peserta Uji'], 'nomor' => ['CARI-001']];
    }

    #[DataProvider('searches')]
    public function test_search_matches_name_or_number_only_within_selected_cohort(string $q): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manajemen']));
        $peserta = PesertaPkpa::factory()->create(['nama_lengkap' => 'Nama Peserta Uji', 'nomor_peserta' => 'CARI-001']);
        $nilai = PenilaianPkpa::factory()->for($peserta, 'peserta')->create();
        $match = HasilPrediksi::factory()->for($nilai, 'penilaian')->create();
        $outside = PesertaPkpa::factory()->create(['nama_lengkap' => 'Nama Peserta Uji Lain', 'nomor_peserta' => 'CARI-001-LUAR']);
        HasilPrediksi::factory()->for(PenilaianPkpa::factory()->for($outside, 'peserta'), 'penilaian')->create();
        $unmatched = PesertaPkpa::factory()->for($peserta->angkatan, 'angkatan')->create(['nama_lengkap' => 'Bukan Hasil Pencarian', 'nomor_peserta' => 'LAIN']);
        HasilPrediksi::factory()->for(PenilaianPkpa::factory()->for($unmatched, 'peserta'), 'penilaian')->create();

        $response = $this->get(route('prediksi.index', ['q' => $q, 'angkatan_id' => $peserta->angkatan_id]));

        $response->assertOk()->assertSee('1 hasil sesuai filter')->assertDontSee('Nama Peserta Uji Lain')->assertDontSee('Bukan Hasil Pencarian');
        $this->assertSame([$match->id], $response->viewData('riwayat')->getCollection()->modelKeys());
    }

    public function test_history_retains_repeated_runs_in_latest_first_order_and_keeps_filters_in_pagination(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $nilai = PenilaianPkpa::factory()->create();
        $model = ModelMl::factory()->for($user, 'pembuat')->create(['nama_versi' => 'RF-Riwayat-Uji']);
        $history = HasilPrediksi::factory()->count(21)->for($nilai, 'penilaian')->for($model, 'modelMl')->for($user, 'prediktor')->create(['tanggal_prediksi' => '2026-11-08 10:00:12']);
        $filters = ['q' => $nilai->peserta->nomor_peserta, 'angkatan_id' => $nilai->peserta->angkatan_id];

        $response = $this->get(route('prediksi.index', [...$filters, 'page' => 2]));

        $response->assertOk()->assertSee('21 hasil sesuai filter')
            ->assertSee('Setiap baris adalah satu hasil prediksi tersimpan. Peserta dapat muncul beberapa kali.')
            ->assertSee('RF-Riwayat-Uji')->assertSee('08/11/2026 10:00:12');
        $paginator = $response->viewData('riwayat');
        $this->assertSame([$history->first()->id], $paginator->getCollection()->modelKeys());
        parse_str(parse_url($paginator->url(1), PHP_URL_QUERY), $query);
        $this->assertSame([...$filters, 'angkatan_id' => (string) $filters['angkatan_id'], 'page' => '1'], $query);
    }

    public function test_detail_shows_current_six_values_with_separate_actual_status_and_complete_escaped_recommendation(): void
    {
        $this->actingAs(User::factory()->create());
        $nilai = PenilaianPkpa::factory()->create([
            'persen_kehadiran' => 42.5, 'nilai_kode_etik' => 61.11, 'nilai_hukum_acara' => 62.22,
            'nilai_tugas_praktik' => 63.33, 'nilai_keaktifan' => 64.44, 'nilai_simulasi_upa' => 65.55,
            'status_aktual' => 'Tidak Lulus',
        ]);
        $recommendation = str_repeat('Pembinaan berdasarkan data simulasi penelitian. ', 5).'<script>alert(1)</script>';
        $result = HasilPrediksi::factory()->for($nilai, 'penilaian')->create([
            'status_prediksi' => 'Lulus', 'probabilitas_lulus' => 91.25, 'probabilitas_gagal' => 8.75,
            'rekomendasi_tindak_lanjut' => $recommendation,
        ]);

        $response = $this->get(route('prediksi.index'));

        $response->assertOk()->assertSee('bukan keputusan kelulusan resmi')
            ->assertSee('Estimasi model')->assertSee('Estimasi kelas Lulus')->assertSee('Estimasi kelas Tidak Lulus')
            ->assertSee('Persentase merupakan estimasi klasifikasi model berdasarkan data simulasi, bukan kepastian kelulusan.')
            ->assertDontSee('Peluang klasifikasi')
            ->assertSee('Riwayat tidak menyimpan salinan nilai saat prediksi.')
            ->assertSee('Status kelulusan aktual saat ini:')->assertSee('Tidak Lulus')
            ->assertSee('91,25%')->assertSee('8,75%')->assertSee('42,50%')
            ->assertSee('61,11')->assertSee('62,22')->assertSee('63,33')->assertSee('64,44')->assertSee('65,55')
            ->assertSee('bukan standar resmi Advokat Propindo.')
            ->assertSee($recommendation)->assertDontSee('<script>alert(1)</script>', false);
        foreach (PenilaianPkpa::FEATURE_LABELS as $label) {
            $response->assertSee($label);
        }
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new DOMXPath($document);
        $summary = $xpath->query('//td[@class="prediksi-summary"]')->item(0)->textContent;
        $this->assertSame('Fokus tidak dirinci', trim($summary));
        $this->assertStringContainsString($recommendation, $xpath->query('//dialog[@id="prediksi-detail-'.$result->id.'"]')->item(0)->textContent);
        $this->assertTrue($response->viewData('riwayat')->first()->relationLoaded('penilaian'));
    }

    public function test_coaching_focus_comes_from_saved_recommendation_and_threshold_wording_is_presentation_only(): void
    {
        $this->actingAs(User::factory()->create());
        $nilai = PenilaianPkpa::factory()->create(array_fill_keys(array_keys(PenilaianPkpa::FEATURE_LABELS), 95));
        $original = 'Ikuti pembinaan KEHADIRAN (standar minimal 80%). Ikuti klinik Hukum Acara (standar minimal 70). Pertahankan kehadiran.';
        $result = HasilPrediksi::factory()->for($nilai, 'penilaian')->create(['rekomendasi_tindak_lanjut' => $original]);

        $response = $this->get(route('prediksi.index'));

        $response->assertOk()->assertSee('Kehadiran · Hukum Acara')
            ->assertSee('Ikuti pembinaan KEHADIRAN (ambang simulasi 80%). Ikuti klinik Hukum Acara (ambang simulasi 70). Pertahankan kehadiran.')
            ->assertSee('Nilai 70 adalah asumsi pemodelan; kehadiran 80% belum diverifikasi sebagai aturan internal resmi.')
            ->assertDontSee('standar minimal');
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new DOMXPath($document);
        $summary = $xpath->query('//td[@class="prediksi-summary"]')->item(0)->textContent;
        $this->assertSame('Kehadiran · Hukum Acara', trim($summary));
        $this->assertSame($original, $result->fresh()->rekomendasi_tindak_lanjut);
    }

    public function test_multiple_coaching_topics_are_compact_in_table_and_fully_available_in_detail(): void
    {
        $this->actingAs(User::factory()->create());
        $result = HasilPrediksi::factory()->create(['rekomendasi_tindak_lanjut' => 'Kehadiran, Kode Etik, Hukum Acara, Tugas Praktik, Keaktifan, dan Simulasi UPA perlu pembinaan.']);

        $response = $this->get(route('prediksi.index'));

        $response->assertOk()->assertSee('Kehadiran · Kode Etik')->assertSee('+4 fokus di detail')
            ->assertSee('Fokus dalam rekomendasi: Kehadiran · Kode Etik · Hukum Acara · Tugas Praktik · Keaktifan · Simulasi UPA.')
            ->assertSee($result->rekomendasi_tindak_lanjut);
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $summary = (new DOMXPath($document))->query('//td[@class="prediksi-summary"]')->item(0)->textContent;
        $this->assertStringNotContainsString('Hukum Acara', $summary);
    }

    public function test_batch_readiness_counts_assessments_including_zero_without_changing_button_behavior(): void
    {
        $this->actingAs(User::factory()->create());
        ModelMl::factory()->create(['is_active' => true]);
        $angkatan = AngkatanPkpa::factory()->create();
        $peserta = PesertaPkpa::factory()->count(3)->for($angkatan, 'angkatan')->create();
        PenilaianPkpa::factory()->for($peserta[0], 'peserta')->create();
        PenilaianPkpa::factory()->for($peserta[1], 'peserta')->create(array_fill_keys(array_keys(PenilaianPkpa::FEATURE_LABELS), 0));
        $other = PenilaianPkpa::factory()->create();
        Http::preventStrayRequests();
        Http::fake();

        $response = $this->get(route('prediksi.index'));

        $response->assertOk()->assertSee('2 dari 3 peserta siap diprediksi.')
            ->assertSee('1 peserta belum memiliki penilaian. Lengkapi enam atribut sebelum memproses seluruh angkatan.');
        $counts = $response->viewData('angkatan')->keyBy('id');
        $this->assertSame(2, $counts[$angkatan->id]->peserta_siap_count);
        $this->assertSame(3, $counts[$angkatan->id]->peserta_count);
        $this->assertSame(1, $counts[$other->peserta->angkatan_id]->peserta_siap_count);
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $xpath->query('//form[@action="'.route('prediksi.angkatan', $angkatan).'"]/button[not(@disabled)]')->length);
        Http::assertNothingSent();
    }

    public function test_zero_valued_assessment_still_passes_existing_batch_prediction_validation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $nilai = PenilaianPkpa::factory()->create(array_fill_keys(array_keys(PenilaianPkpa::FEATURE_LABELS), 0));
        $model = ModelMl::factory()->create(['is_active' => true]);
        Http::preventStrayRequests();
        Http::fake([config('services.ml.url').'/api/predict' => Http::response([
            'file_path_model' => $model->file_path_model,
            'hasil_prediksi' => [[
                'status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 0, 'probabilitas_gagal' => 100,
                'rekomendasi_tindak_lanjut' => 'Pembinaan kehadiran.',
            ]],
        ])]);

        $response = $this->post(route('prediksi.angkatan', $nilai->peserta->angkatan));

        $response->assertSessionHasNoErrors()->assertRedirect(route('prediksi.index', ['angkatan_id' => $nilai->peserta->angkatan_id]));
        Http::assertSent(fn (Request $request): bool => count($request['data']) === 1 && array_values($request['data'][0]) === [0.0, 0.0, 0.0, 0.0, 0.0, 0.0]);
        $this->assertDatabaseHas('hasil_prediksi', ['penilaian_id' => $nilai->id, 'peserta_id' => $nilai->peserta_id, 'diprediksi_oleh' => $user->id]);
    }

    public function test_management_can_view_history_but_has_no_batch_or_individual_prediction_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manajemen']));
        $result = HasilPrediksi::factory()->create();

        $response = $this->get(route('prediksi.index'));

        $response->assertOk()->assertSee('Detail prediksi')->assertSee(route('peserta.show', $result->peserta))
            ->assertDontSee(route('prediksi.individual', $result->peserta))
            ->assertDontSee('Prediksi seluruh peserta')->assertDontSee('Prediksi ulang');
    }

    public function test_missing_active_model_disables_execution_but_keeps_older_results_visible(): void
    {
        $this->actingAs(User::factory()->create());
        $result = HasilPrediksi::factory()->for(ModelMl::factory()->state(['is_active' => false]), 'modelMl')->create();

        $response = $this->get(route('prediksi.index'));

        $response->assertOk()->assertSee('Belum ada model aktif')->assertSee($result->peserta->nama_lengkap)
            ->assertSee('Prediksi ulang belum tersedia karena tidak ada model aktif.');
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        $this->assertGreaterThan(0, $xpath->query('//form[contains(@action,"/prediksi")]/button[@disabled]')->length);
        $this->assertSame(0, $xpath->query('//form[contains(@action,"/prediksi")]/button[not(@disabled)]')->length);
    }

    public function test_admin_keeps_existing_execution_routes_for_running_cohorts_and_individuals(): void
    {
        $this->actingAs(User::factory()->create());
        ModelMl::factory()->create(['is_active' => true]);
        $result = HasilPrediksi::factory()->create();
        $finished = AngkatanPkpa::factory()->create(['status_angkatan' => 'selesai']);

        $response = $this->get(route('prediksi.index'));

        $response->assertOk()->assertSee(route('prediksi.angkatan', $result->peserta->angkatan))
            ->assertSee(route('prediksi.individual', $result->peserta))
            ->assertDontSee(route('prediksi.angkatan', $finished));
    }

    public function test_empty_history_and_no_running_cohorts_provide_clear_next_steps(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('prediksi.index'));

        $response->assertOk()->assertSee('Belum ada riwayat prediksi')->assertSee('Belum ada angkatan berjalan untuk prediksi massal.')
            ->assertSee(route('peserta.index'))->assertDontSee('Hasil prediksi tidak ditemukan');
    }

    public function test_no_search_results_offers_reset_instead_of_empty_history_message(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('prediksi.index', ['q' => 'Tanpa Hasil']));

        $response->assertOk()->assertSee('Hasil prediksi tidak ditemukan')->assertSee('Reset filter')
            ->assertDontSee('Belum ada riwayat prediksi');
    }

    public function test_out_of_range_page_links_to_first_page_with_search_retained(): void
    {
        $this->actingAs(User::factory()->create());
        $result = HasilPrediksi::factory()->create();

        $response = $this->get(route('prediksi.index', ['q' => $result->peserta->nomor_peserta, 'page' => 99]));

        $response->assertOk()->assertSee('Halaman ini tidak memiliki hasil')->assertSee($response->viewData('riwayat')->url(1));
    }

    public function test_oversized_search_is_rejected_with_a_validation_message(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->getJson(route('prediksi.index', ['q' => str_repeat('a', 101)]));

        $response->assertUnprocessable()->assertJsonValidationErrors(['q' => 'Kata pencarian maksimal 100 karakter.']);
    }
}
