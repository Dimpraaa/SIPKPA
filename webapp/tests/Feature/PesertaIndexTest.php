<?php

namespace Tests\Feature;

use App\Models\AngkatanPkpa;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PesertaIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_combined_filters_keep_alphabetical_pagination_and_all_admin_actions(): void
    {
        $this->actingAs(User::factory()->create());
        $angkatan = AngkatanPkpa::factory()->create();
        $matching = collect();
        for ($i = 1; $i <= 21; $i++) {
            $peserta = PesertaPkpa::factory()->for($angkatan, 'angkatan')->create(['nama_lengkap' => sprintf('Andi %02d', $i)]);
            PenilaianPkpa::factory()->for($peserta, 'peserta')->create(['status_aktual' => 'Lulus']);
            $matching->push($peserta);
        }
        $outside = PesertaPkpa::factory()->create(['nama_lengkap' => 'Andi Angkatan Lain']);
        PenilaianPkpa::factory()->for($outside, 'peserta')->create(['status_aktual' => 'Lulus']);
        $differentStatus = PesertaPkpa::factory()->for($angkatan, 'angkatan')->create(['nama_lengkap' => 'Andi Tidak Lulus']);
        PenilaianPkpa::factory()->for($differentStatus, 'peserta')->create(['status_aktual' => 'Tidak Lulus']);
        PesertaPkpa::factory()->for($angkatan, 'angkatan')->create(['nama_lengkap' => 'Andi Belum Dinilai']);
        $filters = ['q' => 'Andi', 'angkatan_id' => $angkatan->id, 'status_aktual' => 'Lulus'];

        $response = $this->get(route('peserta.index', [...$filters, 'page' => 2]));

        $response->assertOk()->assertSee('21 peserta sesuai filter')->assertSee('Andi 21')
            ->assertDontSee('Andi Angkatan Lain')->assertDontSee('Andi Tidak Lulus')->assertDontSee('Andi Belum Dinilai')
            ->assertSee(route('peserta.show', $matching->last()))
            ->assertSee(route('peserta.edit', $matching->last()))
            ->assertSee(route('peserta.khs', $matching->last()))
            ->assertSee(route('penilaian.import'))->assertSee(route('peserta.create'));
        $paginator = $response->viewData('peserta');
        $this->assertSame(21, $paginator->total());
        $this->assertSame([$matching->last()->id], $paginator->getCollection()->modelKeys());
        parse_str(parse_url($paginator->url(1), PHP_URL_QUERY), $query);
        $this->assertSame([...$filters, 'angkatan_id' => (string) $angkatan->id, 'page' => '1'], $query);
    }

    /** @return array<string, array{string}> */
    public static function actualStatuses(): array
    {
        return [
            'lulus aktual' => ['Lulus'],
            'tidak lulus aktual' => ['Tidak Lulus'],
            'nilai ada, status belum ditentukan' => ['Belum Ditentukan'],
            'penilaian belum ada' => ['belum_dinilai'],
        ];
    }

    #[DataProvider('actualStatuses')]
    public function test_status_filter_distinguishes_actual_outcomes_and_missing_assessment(string $status): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manajemen']));
        $angkatan = AngkatanPkpa::factory()->create();
        $participants = [];
        foreach (['Lulus', 'Tidak Lulus', 'Belum Ditentukan'] as $actual) {
            $participants[$actual] = PesertaPkpa::factory()->for($angkatan, 'angkatan')->create();
            PenilaianPkpa::factory()->for($participants[$actual], 'peserta')->create(['status_aktual' => $actual]);
        }
        $participants['belum_dinilai'] = PesertaPkpa::factory()->for($angkatan, 'angkatan')->create();

        $response = $this->get(route('peserta.index', ['status_aktual' => $status]));

        $response->assertOk()->assertSee('Status aktual adalah keputusan akademik, bukan hasil prediksi.');
        $this->assertSame([$participants[$status]->id], $response->viewData('peserta')->getCollection()->modelKeys());
    }

    public function test_search_by_participant_number_escapes_names_and_exposes_only_read_actions_to_management(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manajemen']));
        $peserta = PesertaPkpa::factory()->create(['nama_lengkap' => '<script>alert(1)</script>', 'nomor_peserta' => 'PKPA-CARI-01']);
        PenilaianPkpa::factory()->for($peserta, 'peserta')->create();
        PesertaPkpa::factory()->create(['nomor_peserta' => 'LAIN-01']);

        $response = $this->get(route('peserta.index', ['q' => 'PKPA-CARI-01']));

        $response->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee(route('peserta.show', $peserta))->assertSee(route('peserta.khs', $peserta))
            ->assertDontSee(route('peserta.edit', $peserta))->assertDontSee(route('penilaian.import'))
            ->assertDontSee(route('peserta.create'))->assertDontSee('LAIN-01');
        $this->assertSame([$peserta->id], $response->viewData('peserta')->getCollection()->modelKeys());
    }

    public function test_empty_directory_provides_an_admin_onboarding_action(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('peserta.index'));

        $response->assertOk()->assertSee('Belum ada peserta PKPA')->assertSee(route('peserta.create'))
            ->assertDontSee('Peserta tidak ditemukan');
    }

    public function test_no_search_results_provides_a_reset_instead_of_an_empty_directory_message(): void
    {
        $this->actingAs(User::factory()->create());
        PesertaPkpa::factory()->create(['nama_lengkap' => 'Peserta Terdaftar', 'nomor_peserta' => 'PKPA-01']);

        $response = $this->get(route('peserta.index', ['q' => 'TidakAdaHasil']));

        $response->assertOk()->assertSee('Peserta tidak ditemukan')->assertSee('Reset filter')
            ->assertDontSee('Belum ada peserta PKPA');
    }

    public function test_out_of_range_page_links_back_to_first_page_with_filters(): void
    {
        $this->actingAs(User::factory()->create());
        PesertaPkpa::factory()->create(['nama_lengkap' => 'Peserta Terdaftar']);

        $response = $this->get(route('peserta.index', ['q' => 'Peserta Terdaftar', 'page' => 99]));

        $response->assertOk()->assertSee('Halaman ini tidak memiliki peserta')->assertSee('Ke halaman pertama')
            ->assertSee($response->viewData('peserta')->url(1));
    }

    public function test_unassessed_participant_has_no_khs_action(): void
    {
        $this->actingAs(User::factory()->create());
        $peserta = PesertaPkpa::factory()->create();

        $response = $this->get(route('peserta.index'));

        $response->assertOk()->assertSee('Belum dinilai')->assertSee(route('peserta.edit', $peserta))
            ->assertDontSee(route('peserta.khs', $peserta));
    }

    public function test_invalid_actual_status_returns_a_validation_error(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->getJson(route('peserta.index', ['status_aktual' => 'Berisiko Tidak Lulus']));

        $response->assertUnprocessable()->assertJsonValidationErrors(['status_aktual' => 'Pilihan Status aktual tidak valid.']);
    }
}
