<?php

namespace Tests\Feature;

use App\Models\AngkatanPkpa;
use App\Models\HasilPrediksi;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Models\User;
use App\Services\PenilaianImportService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AcademicWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_creates_updates_and_deletes_cohorts_and_empty_cohort_is_required_for_deletion(): void
    {
        $this->actingAs(User::factory()->create());
        $payload = ['kode_angkatan' => 'PKPA-TEST', 'nama_angkatan' => 'Angkatan Uji', 'tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-03-01', 'status_angkatan' => 'berjalan'];

        $this->post(route('angkatan.store'), $payload)->assertRedirect(route('angkatan.index'));

        $angkatan = AngkatanPkpa::sole();
        $this->get(route('angkatan.edit', $angkatan))->assertOk();
        $this->put(route('angkatan.update', $angkatan), [...$payload, 'status_angkatan' => 'selesai'])->assertSessionHasNoErrors();
        $this->assertSame('selesai', $angkatan->fresh()->status_angkatan);
        PesertaPkpa::factory()->for($angkatan, 'angkatan')->create();
        $this->delete(route('angkatan.destroy', $angkatan))->assertSessionHasErrors('angkatan');
        $this->assertModelExists($angkatan);
        $empty = AngkatanPkpa::factory()->create();
        $this->delete(route('angkatan.destroy', $empty))->assertRedirect(route('angkatan.index'));
        $this->assertModelMissing($empty);
        $this->post(route('angkatan.store'), [...$payload, 'kode_angkatan' => 'OTHER', 'tanggal_selesai' => '2025-01-01'])
            ->assertSessionHasErrors(['tanggal_selesai' => 'Tanggal selesai harus sama dengan atau setelah Tanggal mulai.']);
    }

    public function test_participant_crud_calculates_average_server_side_and_escapes_names(): void
    {
        $this->actingAs(User::factory()->create());
        $angkatan = AngkatanPkpa::factory()->create();
        $payload = $this->participantPayload($angkatan);
        $this->get(route('peserta.create'))->assertOk();

        $this->post(route('peserta.store'), [...$payload, 'nilai_akhir_rata' => 999])->assertSessionHasNoErrors();

        $peserta = PesertaPkpa::sole();
        $this->assertSame('80.00', $peserta->penilaian->nilai_akhir_rata);
        $this->get(route('peserta.show', $peserta))->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('peserta.edit', $peserta))->assertOk();
        $this->put(route('peserta.update', $peserta), [...$payload, 'nilai_simulasi_upa' => 100])->assertSessionHasNoErrors();
        $this->assertSame('88.00', $peserta->fresh()->penilaian->nilai_akhir_rata);
        $this->post(route('peserta.store'), $payload)->assertSessionHasErrors('nomor_peserta');
        $this->put(route('peserta.update', $peserta), [...$payload, 'nilai_hukum_acara' => 101])
            ->assertSessionHasErrors(['nilai_hukum_acara' => 'Nilai Hukum Acara harus antara 0 dan 100.']);
        $this->delete(route('peserta.destroy', $peserta))->assertRedirect(route('peserta.index'));
        $this->assertDatabaseCount('penilaian_pkpa', 0);
        $this->assertModelMissing($peserta);
    }

    public function test_prediction_history_prevents_participant_deletion(): void
    {
        $this->actingAs(User::factory()->create());
        $hasil = HasilPrediksi::factory()->create();

        $this->delete(route('peserta.destroy', $hasil->peserta))->assertSessionHasErrors('peserta');

        $this->assertModelExists($hasil);
        $this->assertModelExists($hasil->peserta);
    }

    public function test_csv_import_updates_values_atomically_and_downloads_usable_template(): void
    {
        $this->actingAs(User::factory()->create());
        $nilai = PenilaianPkpa::factory()->create();
        $participant = $nilai->peserta;
        $row = [$participant->nomor_peserta, 85, 80, 70, 90, 100, 60, 'Lulus'];
        $csv = implode(',', PenilaianImportService::headers())."\n".implode(',', $row);

        $this->post(route('penilaian.import.store'), ['angkatan_id' => $participant->angkatan_id, 'file' => UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
            ->assertSessionHasNoErrors()->assertRedirect(route('peserta.index', ['angkatan_id' => $participant->angkatan_id]));

        $this->assertSame('80.00', $nilai->fresh()->nilai_akhir_rata);
        $this->assertSame('Lulus', $nilai->fresh()->status_aktual);
        $this->get(route('penilaian.template'))->assertDownload('template-penilaian-pkpa.csv')
            ->assertStreamedContent("\xEF\xBB\xBF".implode(',', PenilaianImportService::headers())."\n");

        $invalid = implode(',', PenilaianImportService::headers())."\n".implode(',', [$participant->nomor_peserta, 90, 90, 90, 90, 90, 90, 'Tidak Lulus'])."\nMISSING,85,80,70,90,100,60,Lulus";
        $this->post(route('penilaian.import.store'), ['angkatan_id' => $participant->angkatan_id, 'file' => UploadedFile::fake()->createWithContent('nilai.csv', $invalid)])
            ->assertSessionHasErrors('file');
        $this->assertSame('80.00', $nilai->fresh()->nilai_akhir_rata);
        $this->assertSame('Lulus', $nilai->fresh()->status_aktual);
    }

    public function test_excel_import_reads_xlsx_and_rejects_duplicate_or_out_of_range_rows(): void
    {
        $this->actingAs(User::factory()->create());
        $nilai = PenilaianPkpa::factory()->create();
        $participant = $nilai->peserta;
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([PenilaianImportService::headers(), [$participant->nomor_peserta, 88, 80, 70, 90, 100, 60, 'Lulus']]);
        ob_start();
        (new Xlsx($sheet))->save('php://output');
        $contents = ob_get_clean();
        $sheet->disconnectWorksheets();

        $this->post(route('penilaian.import.store'), ['angkatan_id' => $participant->angkatan_id, 'file' => UploadedFile::fake()->createWithContent('nilai.xlsx', $contents)])
            ->assertSessionHasNoErrors();

        $this->assertSame('88.00', $nilai->fresh()->persen_kehadiran);
        $this->assertSame('80.00', $nilai->fresh()->nilai_akhir_rata);
        foreach (["{$participant->nomor_peserta},90,90,90,90,90,90,Lulus\n{$participant->nomor_peserta},80,80,80,80,80,80,Lulus", "{$participant->nomor_peserta},101,80,80,80,80,80,Lulus"] as $rows) {
            $csv = implode(',', PenilaianImportService::headers())."\n".$rows;
            $this->post(route('penilaian.import.store'), ['angkatan_id' => $participant->angkatan_id, 'file' => UploadedFile::fake()->createWithContent('nilai.csv', $csv)])
                ->assertSessionHasErrors('file');
            $this->assertSame('88.00', $nilai->fresh()->persen_kehadiran);
        }
    }

    /** @return array<string, mixed> */
    private function participantPayload(AngkatanPkpa $angkatan): array
    {
        return [
            'angkatan_id' => $angkatan->id, 'nomor_peserta' => 'TEST-001', 'nama_lengkap' => '<script>alert(1)</script>',
            'jenis_kelamin' => 'L', 'email' => 'peserta@example.com', 'no_hp' => '081234567890',
            'asal_kampus_s1' => 'Universitas Indonesia', 'ipk_s1' => 3.5,
            'persen_kehadiran' => 85, 'nilai_kode_etik' => 80, 'nilai_hukum_acara' => 70,
            'nilai_tugas_praktik' => 90, 'nilai_keaktifan' => 100, 'nilai_simulasi_upa' => 60, 'status_aktual' => 'Belum Ditentukan',
        ];
    }
}
