<?php

namespace Tests\Feature;

use App\Models\AngkatanPkpa;
use App\Models\HasilPrediksi;
use App\Models\ModelMl;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PeringatanDiniTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_indicators_use_existing_strict_thresholds_and_zero_is_a_real_value(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['role' => 'manajemen']);
        $this->actingAs($user);
        $nilai = PenilaianPkpa::factory()->create([
            'persen_kehadiran' => 79.99, 'nilai_kode_etik' => 70, 'nilai_hukum_acara' => 69.99,
            'nilai_tugas_praktik' => 0, 'nilai_keaktifan' => 80, 'nilai_simulasi_upa' => 90,
            'status_aktual' => 'Lulus',
        ]);
        $recommendation = 'Pembinaan Hukum Acara (standar minimal 70). <script>alert(1)</script>';
        $prediction = HasilPrediksi::factory()->for($nilai, 'penilaian')->create([
            'status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 12.75, 'probabilitas_gagal' => 87.25,
            'rekomendasi_tindak_lanjut' => $recommendation, 'tanggal_prediksi' => now()->subDay(),
        ]);

        $response = $this->get(route('laporan.berisiko'));

        $response->assertOk()->assertSee('Peringatan Dini')->assertSee('79,99%')->assertSee('0,00')
            ->assertSee('+1 indikator lainnya di detail')->assertSee('Status kelulusan aktual')
            ->assertSee('Prediksi Random Forest terbaru')->assertSee('Indikator penilaian terkini')
            ->assertSee('12,75%')->assertSee('87,25%')->assertSee('bukan kepastian kelulusan')
            ->assertSee('Nilai telah diperbarui. Jalankan ulang prediksi untuk memperbarui rekomendasi.')
            ->assertSee('Pembinaan Hukum Acara (ambang evaluasi simulasi 70). <script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('standar minimal')
            ->assertSee('ketentuan sertifikat PKPA PERADI')->assertSee('belum diverifikasi');
        foreach (['Kehadiran', 'Kode Etik', 'Hukum Acara', 'Tugas Praktik', 'Keaktifan', 'Simulasi UPA'] as $label) {
            $response->assertSee($label);
        }
        $row = $response->viewData('rows')[0];
        $this->assertSame(['persen_kehadiran', 'nilai_hukum_acara', 'nilai_tugas_praktik'], $row['indikator']);
        $this->assertSame('Lulus', $row['status_aktual']);
        $this->assertSame('Berisiko Tidak Lulus', $row['status_prediksi']);
        $this->assertSame($recommendation, $prediction->fresh()->rekomendasi_tindak_lanjut);
    }

    public function test_model_membership_is_not_replaced_by_current_rule_indicators(): void
    {
        $this->actingAs(User::factory()->create());
        $high = PenilaianPkpa::factory()->create(array_fill_keys(array_keys(PenilaianPkpa::FEATURE_LABELS), 95));
        HasilPrediksi::factory()->for($high, 'penilaian')->create(['status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 15, 'probabilitas_gagal' => 85]);
        $low = PenilaianPkpa::factory()->create(array_fill_keys(array_keys(PenilaianPkpa::FEATURE_LABELS), 0));
        HasilPrediksi::factory()->for($low, 'penilaian')->create(['status_prediksi' => 'Berisiko Tidak Lulus', 'tanggal_prediksi' => now()->subDay()]);
        HasilPrediksi::factory()->for($low, 'penilaian')->create(['status_prediksi' => 'Lulus']);
        PenilaianPkpa::factory()->create(array_fill_keys(array_keys(PenilaianPkpa::FEATURE_LABELS), 0));

        $response = $this->get(route('laporan.berisiko'));

        $response->assertOk()->assertSee('Tidak ada nilai di bawah ambang');
        $this->assertSame([$high->peserta_id], array_column($response->viewData('rows'), 'id'));
        $this->assertSame([], $response->viewData('rows')[0]['indikator']);
    }

    public function test_priority_sort_uses_latest_prediction_timestamp_and_id_with_stable_name_ties(): void
    {
        $this->freezeTime();
        $this->actingAs(User::factory()->create());
        $model = ModelMl::factory()->create();
        $a = PenilaianPkpa::factory()->for(PesertaPkpa::factory()->state(['nama_lengkap' => 'Andi']), 'peserta')->create();
        $b = PenilaianPkpa::factory()->for(PesertaPkpa::factory()->state(['nama_lengkap' => 'Budi']), 'peserta')->create();
        $c = PenilaianPkpa::factory()->for(PesertaPkpa::factory()->state(['nama_lengkap' => 'Citra']), 'peserta')->create();
        HasilPrediksi::factory()->for($a, 'penilaian')->for($model, 'modelMl')->create(['status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 40, 'probabilitas_gagal' => 60]);
        HasilPrediksi::factory()->for($a, 'penilaian')->for($model, 'modelMl')->create(['status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 1, 'probabilitas_gagal' => 99, 'tanggal_prediksi' => now()->subDay()]);
        HasilPrediksi::factory()->for($b, 'penilaian')->for($model, 'modelMl')->create(['status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 50, 'probabilitas_gagal' => 50]);
        HasilPrediksi::factory()->for($b, 'penilaian')->for($model, 'modelMl')->create(['status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 5, 'probabilitas_gagal' => 95]);
        HasilPrediksi::factory()->for($c, 'penilaian')->for($model, 'modelMl')->create(['status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 5, 'probabilitas_gagal' => 95]);

        $response = $this->get(route('laporan.berisiko', ['urutan' => 'estimasi_tertinggi']));

        $response->assertOk();
        $this->assertSame([$b->peserta_id, $c->peserta_id, $a->peserta_id], array_column($response->viewData('rows'), 'id'));
        $this->assertSame([95.0, 95.0, 60.0], array_column($response->viewData('rows'), 'probabilitas_gagal'));
    }

    public function test_combined_filters_and_sort_survive_pagination_and_excel_exports_all_matches(): void
    {
        $user = User::factory()->create(['role' => 'manajemen']);
        $this->actingAs($user);
        $angkatan = AngkatanPkpa::factory()->create();
        $model = ModelMl::factory()->for($user, 'pembuat')->create();
        for ($i = 1; $i <= 21; $i++) {
            $peserta = PesertaPkpa::factory()->for($angkatan, 'angkatan')->create(['nama_lengkap' => sprintf('Peserta Uji %02d', $i)]);
            $nilai = PenilaianPkpa::factory()->for($peserta, 'peserta')->create();
            HasilPrediksi::factory()->for($nilai, 'penilaian')->for($model, 'modelMl')->for($user, 'prediktor')->create([
                'status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 20 - $i / 2, 'probabilitas_gagal' => 80 + $i / 2,
            ]);
        }
        $outside = PenilaianPkpa::factory()->for(PesertaPkpa::factory()->state(['nama_lengkap' => 'Peserta Uji di Luar Angkatan']), 'peserta')->create();
        HasilPrediksi::factory()->for($outside, 'penilaian')->create(['status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 1, 'probabilitas_gagal' => 99]);
        $low = PenilaianPkpa::factory()->for(PesertaPkpa::factory()->for($angkatan, 'angkatan')->state(['nama_lengkap' => 'Peserta Uji Estimasi Rendah']), 'peserta')->create();
        HasilPrediksi::factory()->for($low, 'penilaian')->create(['status_prediksi' => 'Berisiko Tidak Lulus', 'probabilitas_lulus' => 30, 'probabilitas_gagal' => 70]);
        $filters = ['q' => 'Peserta Uji', 'angkatan_id' => $angkatan->id, 'min_gagal' => 80, 'urutan' => 'estimasi_tertinggi'];

        $response = $this->get(route('laporan.berisiko', [...$filters, 'page' => 2]));

        $response->assertOk()->assertSee('21')->assertSee('Peserta Uji 01')->assertDontSee('Peserta Uji di Luar Angkatan')->assertDontSee('Peserta Uji Estimasi Rendah')
            ->assertSee(route('laporan.berisiko.export', ['format' => 'excel']));
        $this->assertSame(21, $response->viewData('total'));
        $this->assertSame(['Peserta Uji 01'], array_column($response->viewData('rows'), 'nama_lengkap'));
        parse_str(parse_url($response->viewData('pagination')->url(1), PHP_URL_QUERY), $query);
        $this->assertSame('estimasi_tertinggi', $query['urutan']);
        $this->assertSame('80', $query['min_gagal']);
        $this->assertSame('Peserta Uji', $query['q']);
        $this->assertSame((string) $angkatan->id, $query['angkatan_id']);
        $export = $this->get(route('laporan.berisiko.export', ['format' => 'excel', ...$filters, 'page' => 2]));
        $export->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $file = UploadedFile::fake()->createWithContent('peringatan.xlsx', $export->streamedContent());
        $book = IOFactory::load($file->getRealPath());
        try {
            $this->assertSame(22, $book->getActiveSheet()->getHighestDataRow());
            $this->assertSame('Peserta Uji 21', $book->getActiveSheet()->getCell('C2')->getValue());
            $this->assertSame('Peserta Uji 01', $book->getActiveSheet()->getCell('C22')->getValue());
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function test_empty_monitoring_explains_coverage_without_claiming_everyone_is_safe(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('laporan.berisiko'));

        $response->assertOk()->assertSee('Belum ada peserta dalam daftar pemantauan')
            ->assertSee('Daftar ini hanya memuat peserta dengan prediksi terbaru Berisiko Tidak Lulus.')
            ->assertSee(route('peserta.index'))->assertDontSee('Semua peserta aman');
    }

    public function test_no_search_results_has_reset_action(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('laporan.berisiko', ['q' => 'Tanpa hasil']));

        $response->assertOk()->assertSee('Tidak ada peserta yang sesuai filter')->assertSee('Reset filter');
    }

    public function test_out_of_range_page_offers_first_page_with_filter_and_sort(): void
    {
        $this->actingAs(User::factory()->create());
        $result = HasilPrediksi::factory()->create(['status_prediksi' => 'Berisiko Tidak Lulus']);

        $response = $this->get(route('laporan.berisiko', ['q' => $result->peserta->nomor_peserta, 'urutan' => 'estimasi_tertinggi', 'page' => 99]));

        $response->assertOk()->assertSee('Halaman ini tidak memiliki peserta')->assertSee($response->viewData('pagination')->url(1));
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function sortEndpoints(): array
    {
        return [
            'halaman' => ['laporan.berisiko', []],
            'pdf' => ['laporan.berisiko.export', ['format' => 'pdf']],
            'excel' => ['laporan.berisiko.export', ['format' => 'excel']],
        ];
    }

    #[DataProvider('sortEndpoints')]
    public function test_invalid_sort_is_rejected_by_page_and_exports(string $route, array $parameters): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->getJson(route($route, [...$parameters, 'urutan' => 'skor_baru']));

        $response->assertUnprocessable()->assertJsonValidationErrors('urutan');
    }
}
