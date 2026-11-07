<?php

namespace App\Services;

use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AcademicReportService
{
    /** @return array<string, int> */
    public static function standards(): array
    {
        return ['persen_kehadiran' => 80, ...array_fill_keys(array_slice(array_keys(PenilaianPkpa::FEATURE_LABELS), 1), 70)];
    }

    /** @param array<string, mixed> $filters
     * @return Builder<PesertaPkpa>
     */
    public function participants(array $filters, bool $risiko): Builder
    {
        return PesertaPkpa::with(['angkatan', 'penilaian', 'prediksiTerbaru.modelMl'])
            ->when($filters['angkatan_id'] ?? null, fn (Builder $query, int $id): Builder => $query->where('angkatan_id', $id))
            ->when($filters['q'] ?? null, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('nama_lengkap', 'like', '%'.$search.'%')->orWhere('nomor_peserta', 'like', '%'.$search.'%');
            }))
            ->when($risiko, fn (Builder $query): Builder => $query->whereHas('prediksiTerbaru', function (Builder $query) use ($filters): void {
                $query->where('status_prediksi', 'Berisiko Tidak Lulus')
                    ->where('probabilitas_gagal', '>=', $filters['min_gagal'] ?? 0);
            }))
            ->orderBy('nama_lengkap')->orderBy('id');
    }

    /** @return array<string, string> */
    public static function columns(bool $risiko): array
    {
        $columns = ['no' => 'No.', 'nomor_peserta' => 'Nomor Peserta', 'nama_lengkap' => 'Nama Lengkap', 'angkatan' => 'Angkatan', ...PenilaianPkpa::FEATURE_LABELS];
        if (! $risiko) {
            $columns += ['nilai_akhir_rata' => 'Rata-rata', 'status_aktual' => 'Status Aktual', 'status_prediksi' => 'Prediksi Terbaru', 'probabilitas_lulus' => 'Peluang Lulus (%)'];
        }

        return $columns + ['probabilitas_gagal' => 'Peluang Gagal (%)', 'model' => 'Model', 'tanggal_prediksi' => 'Waktu Prediksi', 'rekomendasi' => 'Rekomendasi Tindak Lanjut'];
    }

    /** @param Collection<int, PesertaPkpa> $participants
     * @return list<array<string, mixed>>
     */
    public function rows(Collection $participants, int $offset = 0): array
    {
        return $participants->values()->map(function (PesertaPkpa $peserta, int $index) use ($offset): array {
            $nilai = $peserta->penilaian;
            $prediksi = $peserta->prediksiTerbaru;
            $row = [
                'id' => $peserta->id, 'no' => $offset + $index + 1, 'nomor_peserta' => $peserta->nomor_peserta,
                'nama_lengkap' => $peserta->nama_lengkap, 'angkatan' => $peserta->angkatan->nama_angkatan,
            ];
            foreach (array_keys(PenilaianPkpa::FEATURE_LABELS) as $feature) {
                $row[$feature] = $nilai !== null ? (float) $nilai->{$feature} : null;
            }

            return [...$row,
                'nilai_akhir_rata' => $nilai !== null ? (float) $nilai->nilai_akhir_rata : null,
                'status_aktual' => $nilai?->status_aktual ?? 'Belum dinilai',
                'status_prediksi' => $prediksi?->status_prediksi ?? 'Belum diprediksi',
                'probabilitas_lulus' => $prediksi !== null ? (float) $prediksi->probabilitas_lulus : null,
                'probabilitas_gagal' => $prediksi !== null ? (float) $prediksi->probabilitas_gagal : null,
                'model' => $prediksi?->modelMl->nama_versi,
                'tanggal_prediksi' => $prediksi?->tanggal_prediksi->format('d/m/Y H:i'),
                'rekomendasi' => $prediksi?->rekomendasi_tindak_lanjut,
                'nilai_diperbarui' => $nilai && $prediksi && $nilai->updated_at->greaterThan($prediksi->tanggal_prediksi),
            ];
        })->all();
    }

    /** @return array{labels: list<string>, values: list<float>, standards: list<int>}|null */
    public function radarData(PesertaPkpa $peserta): ?array
    {
        if (! $peserta->penilaian) {
            return null;
        }

        return [
            'labels' => array_values(PenilaianPkpa::FEATURE_LABELS),
            'values' => array_map(fn (string $feature): float => (float) $peserta->penilaian->{$feature}, array_keys(PenilaianPkpa::FEATURE_LABELS)),
            'standards' => array_values(self::standards()),
        ];
    }
}
