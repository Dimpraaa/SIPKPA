<?php

namespace Tests\Feature;

use App\Models\AngkatanPkpa;
use App\Models\HasilPrediksi;
use App\Models\ModelMl;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Models\User;
use App\Services\AcademicReportService;
use App\Services\RadarChartService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_risk_report_filters_latest_predictions_and_exports_safe_styled_xlsx_and_real_pdf(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['role' => 'manajemen']);
        $angkatan = AngkatanPkpa::factory()->create();
        $other = AngkatanPkpa::factory()->create();
        $model = ModelMl::factory()->for($user, 'pembuat')->create();
        $risk = $this->participant($angkatan, '=1+1', '00123', [79.99, 69.99, 70, 80, 90, 0]);
        $passed = $this->participant($angkatan, 'Sudah Lulus', '00124');
        $outside = $this->participant($other, 'Angkatan Lain', '00125');
        $this->prediction($risk, $model, $user, 'Berisiko Tidak Lulus', '2026-11-07 10:00:00');
        $this->prediction($passed, $model, $user, 'Lulus', '2026-11-07 10:00:00');
        $this->prediction($passed, $model, $user, 'Berisiko Tidak Lulus', '2026-11-06 10:00:00');
        $this->prediction($outside, $model, $user, 'Berisiko Tidak Lulus', '2026-11-07 10:00:00');
        $filters = ['angkatan_id' => $angkatan->id, 'min_gagal' => 80, 'q' => '00123'];
        $this->actingAs($user);

        $response = $this->get(route('laporan.berisiko', $filters));

        $response->assertOk()->assertSee('=1+1')->assertDontSee('Sudah Lulus')->assertDontSee('Angkatan Lain');
        $this->assertSame(1, $response->viewData('total'));
        $row = $response->viewData('rows')[0];
        $this->assertSame(79.99, $row['persen_kehadiran']);
        $this->assertSame(90.0, $row['probabilitas_gagal']);
        $book = $this->excel(route('laporan.berisiko.export', ['format' => 'excel', ...$filters]));
        try {
            $sheet = $book->getActiveSheet();
            $this->assertSame(2, $sheet->getHighestDataRow());
            $this->assertSame('00123', $sheet->getCell('B2')->getValue());
            $this->assertSame('=1+1', $sheet->getCell('C2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('C2')->getDataType());
            $this->assertSame(79.99, $sheet->getCell('E2')->getValue());
            $this->assertSame('FEE2E2', $sheet->getStyle('E2')->getFill()->getStartColor()->getRGB());
            $this->assertSame('FEE2E2', $sheet->getStyle('F2')->getFill()->getStartColor()->getRGB());
            $this->assertNotSame('FEE2E2', $sheet->getStyle('G2')->getFill()->getStartColor()->getRGB());
            $this->assertSame(90.0, $sheet->getCell('K2')->getValue());
            $this->assertSame('Pembinaan dan remedial Hukum Acara.', $sheet->getCell('N2')->getValue());
        } finally {
            $book->disconnectWorksheets();
        }
        $pdf = $this->get(route('laporan.berisiko.export', ['format' => 'pdf', ...$filters]));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_recap_includes_unpredicted_and_unassessed_participants_only_in_selected_cohort(): void
    {
        $this->actingAs(User::factory()->create());
        $angkatan = AngkatanPkpa::factory()->create();
        $this->participant($angkatan, 'Ada Nilai', 'A1');
        PesertaPkpa::factory()->for($angkatan, 'angkatan')->create(['nama_lengkap' => 'Belum Dinilai']);
        PesertaPkpa::factory()->create(['nama_lengkap' => 'Di Luar Angkatan']);

        $response = $this->get(route('laporan.rekap', ['angkatan_id' => $angkatan->id]));

        $response->assertOk()->assertSee('Belum Dinilai')->assertSee('Belum diprediksi')->assertDontSee('Di Luar Angkatan');
        $this->assertSame(2, $response->viewData('total'));
        $rows = collect($response->viewData('rows'))->keyBy('nama_lengkap');
        $this->assertNull($rows['Belum Dinilai']['persen_kehadiran']);
        $this->assertNull($rows['Ada Nilai']['probabilitas_lulus']);
        $book = $this->excel(route('laporan.rekap.export', ['format' => 'excel', 'angkatan_id' => $angkatan->id]));
        $this->assertSame(3, $book->getActiveSheet()->getHighestDataRow());
        $book->disconnectWorksheets();
        $pdf = $this->get(route('laporan.rekap.export', ['format' => 'pdf', 'angkatan_id' => $angkatan->id]));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_export_contains_all_matching_rows_instead_of_only_current_page(): void
    {
        $this->actingAs(User::factory()->create());
        $angkatan = AngkatanPkpa::factory()->create();
        PesertaPkpa::factory()->count(23)->for($angkatan, 'angkatan')->create();

        $response = $this->get(route('laporan.rekap', ['angkatan_id' => $angkatan->id, 'page' => 2]));

        $response->assertOk();
        $this->assertCount(3, $response->viewData('rows'));
        $this->assertSame(21, $response->viewData('rows')[0]['no']);
        $book = $this->excel(route('laporan.rekap.export', ['format' => 'excel', 'angkatan_id' => $angkatan->id, 'page' => 2]));
        $this->assertSame(24, $book->getActiveSheet()->getHighestDataRow());
        $book->disconnectWorksheets();
    }

    public function test_khs_contains_real_radar_png_and_latest_evaluation_and_detail_has_chart_payload(): void
    {
        $user = User::factory()->create();
        $model = ModelMl::factory()->for($user, 'pembuat')->create();
        $peserta = $this->participant(AngkatanPkpa::factory()->create(), 'Peserta KHS', 'KHS1', [0, 70, 80, 90, 100, 50]);
        $this->prediction($peserta, $model, $user, 'Berisiko Tidak Lulus', '2026-11-07 10:00:00');
        $this->actingAs($user);

        $detail = $this->get(route('peserta.show', $peserta));

        $detail->assertOk()->assertSee('Cetak KHS & Evaluasi (PDF)')->assertSee('id="participant-radar"', false)
            ->assertSee('Pembinaan dan remedial Hukum Acara.');
        $this->assertSame([0.0, 70.0, 80.0, 90.0, 100.0, 50.0], $detail->viewData('radarData')['values']);
        $this->assertSame([80, 70, 70, 70, 70, 70], $detail->viewData('radarData')['standards']);
        $image = app(RadarChartService::class)->pngDataUri($detail->viewData('radarData')['values'], array_values(AcademicReportService::standards()));
        $dimensions = getimagesizefromstring(base64_decode(substr($image, strlen('data:image/png;base64,'))));
        $this->assertSame([620, 460], [$dimensions[0], $dimensions[1]]);
        $pdf = $this->get(route('peserta.khs', $peserta));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertMatchesRegularExpression('~/Subtype\s*/Image~', $pdf->getContent());
    }

    public function test_model_report_filters_experiments_and_generates_pdf(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manajemen']));
        ModelMl::factory()->create(['nama_versi' => 'RF-Gini', 'criterion' => 'gini']);
        ModelMl::factory()->create(['nama_versi' => 'RF-Entropy', 'criterion' => 'entropy', 'n_estimators' => 200, 'max_depth' => 8]);

        $response = $this->get(route('laporan.model', ['criterion' => 'entropy']));

        $response->assertOk()->assertSee('RF-Entropy')->assertDontSee('RF-Gini');
        $this->assertSame(1, $response->viewData('models')->total());
        $pdf = $this->get(route('laporan.model.pdf', ['criterion' => 'entropy']));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_empty_reports_render_and_exports_are_valid_without_missing_grades_or_prediction_errors(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['laporan.berisiko', 'laporan.rekap', 'laporan.model'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $book = $this->excel(route('laporan.berisiko.export', ['format' => 'excel']));
        $this->assertSame(1, $book->getActiveSheet()->getHighestDataRow());
        $book->disconnectWorksheets();
        $peserta = PesertaPkpa::factory()->create();
        $this->get(route('peserta.show', $peserta))->assertOk()->assertDontSee('id="participant-radar"', false);
        $this->get(route('peserta.khs', $peserta))->assertUnprocessable();
    }

    public function test_report_routes_require_login_and_reject_invalid_filters_and_formats(): void
    {
        foreach (['laporan.berisiko', 'laporan.rekap', 'laporan.model', 'laporan.model.pdf'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        foreach (['laporan.berisiko.export', 'laporan.rekap.export'] as $route) {
            $this->get(route($route, ['format' => 'pdf']))->assertRedirect(route('login'));
            $this->get(route($route, ['format' => 'excel']))->assertRedirect(route('login'));
        }
        $this->actingAs(User::factory()->create());
        $this->getJson(route('laporan.berisiko', ['min_gagal' => 101]))->assertUnprocessable()->assertJsonValidationErrors('min_gagal');
        $this->getJson(route('laporan.rekap.export', ['format' => 'pdf', 'angkatan_id' => '1 OR 1=1']))->assertUnprocessable()->assertJsonValidationErrors('angkatan_id');
        $this->get(route('laporan.berisiko.export', ['format' => 'html']))->assertNotFound();
        $this->getJson(route('laporan.model.pdf', ['criterion' => 'other']))->assertUnprocessable()->assertJsonValidationErrors('criterion');
    }

    /** @param list<int|float> $scores */
    private function participant(AngkatanPkpa $angkatan, string $name, string $number, array $scores = [85, 80, 75, 70, 90, 65]): PesertaPkpa
    {
        $peserta = PesertaPkpa::factory()->for($angkatan, 'angkatan')->create(['nama_lengkap' => $name, 'nomor_peserta' => $number]);
        PenilaianPkpa::factory()->for($peserta, 'peserta')->create(array_combine(array_keys(PenilaianPkpa::FEATURE_LABELS), $scores));

        return $peserta;
    }

    private function prediction(PesertaPkpa $peserta, ModelMl $model, User $user, string $status, string $date): void
    {
        HasilPrediksi::factory()->for($peserta->penilaian, 'penilaian')->for($model, 'modelMl')->for($user, 'prediktor')->create([
            'status_prediksi' => $status, 'tanggal_prediksi' => $date, 'probabilitas_lulus' => $status === 'Lulus' ? 95 : 10,
            'probabilitas_gagal' => $status === 'Lulus' ? 5 : 90, 'rekomendasi_tindak_lanjut' => 'Pembinaan dan remedial Hukum Acara.',
        ]);
    }

    private function excel(string $url): Spreadsheet
    {
        $response = $this->get($url);
        $response->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $file = UploadedFile::fake()->createWithContent('report.xlsx', $response->streamedContent());

        return IOFactory::load($file->getRealPath());
    }
}
