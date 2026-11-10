<?php

namespace App\Http\Controllers;

use App\Models\AngkatanPkpa;
use App\Models\HasilPrediksi;
use App\Models\ModelMl;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate(['angkatan_id' => ['nullable', 'integer', 'exists:angkatan_pkpa,id']]);
        $angkatanId = isset($filters['angkatan_id']) ? (int) $filters['angkatan_id'] : null;
        $angkatan = AngkatanPkpa::withCount('peserta')->orderBy('tanggal_mulai')->orderBy('id')->get();
        $angkatanTerpilih = $angkatanId !== null ? $angkatan->firstWhere('id', $angkatanId) : null;
        $modelAktif = ModelMl::where('is_active', true)->first();

        $prediksiTerbaru = $this->latestPredictions($angkatanId);
        $proporsi = (clone $prediksiTerbaru)->selectRaw('status_prediksi, COUNT(*) as jumlah')
            ->groupBy('status_prediksi')->pluck('jumlah', 'status_prediksi');
        $jumlahPeserta = PesertaPkpa::when($angkatanId !== null, fn (Builder $query): Builder => $query->where('angkatan_id', $angkatanId))->count();
        $lulus = (int) ($proporsi['Lulus'] ?? 0);
        $berisiko = (int) ($proporsi['Berisiko Tidak Lulus'] ?? 0);
        $terprediksi = $lulus + $berisiko;
        $ringkasan = [
            'peserta' => $jumlahPeserta,
            'lulus' => $lulus,
            'berisiko' => $berisiko,
            'terprediksi' => $terprediksi,
            'belum_prediksi' => $jumlahPeserta - $terprediksi,
            'cakupan' => $jumlahPeserta > 0 ? round($terprediksi / $jumlahPeserta * 100, 2) : null,
        ];

        $agregatNilai = PenilaianPkpa::join('peserta_pkpa', 'peserta_pkpa.id', '=', 'penilaian_pkpa.peserta_id')
            ->when($angkatanId !== null, fn (Builder $query): Builder => $query->where('peserta_pkpa.angkatan_id', $angkatanId))
            ->select('peserta_pkpa.angkatan_id')
            ->selectRaw('COUNT(*) as jumlah_penilaian')
            ->selectRaw('AVG(penilaian_pkpa.nilai_akhir_rata) as nilai_akhir_rata')
            ->selectRaw('SUM(CASE WHEN status_aktual = ? THEN 1 ELSE 0 END) as jumlah_lulus', ['Lulus'])
            ->selectRaw('SUM(CASE WHEN status_aktual IN (?, ?) THEN 1 ELSE 0 END) as jumlah_ditentukan', ['Lulus', 'Tidak Lulus']);
        foreach (array_keys(PenilaianPkpa::FEATURE_LABELS) as $feature) {
            $agregatNilai->selectRaw('AVG(penilaian_pkpa.'.$feature.') as '.$feature);
        }
        $agregatNilai = $agregatNilai->groupBy('peserta_pkpa.angkatan_id')->get()->keyBy('angkatan_id');

        $angkatanDitampilkan = $angkatanId !== null ? $angkatan->where('id', $angkatanId) : $angkatan;
        $tren = $angkatanDitampilkan->map(function (AngkatanPkpa $item) use ($agregatNilai): array {
            $agregat = $agregatNilai->get($item->id);
            $rataRata = [];
            foreach (array_keys(PenilaianPkpa::FEATURE_LABELS) as $feature) {
                $rataRata[$feature] = $agregat !== null ? round((float) $agregat->{$feature}, 2) : null;
            }
            $ditentukan = (int) ($agregat?->jumlah_ditentukan ?? 0);
            $lulus = (int) ($agregat?->jumlah_lulus ?? 0);

            return [
                'angkatan' => $item->nama_angkatan,
                'peserta' => $item->peserta_count,
                'jumlah_penilaian' => (int) ($agregat?->jumlah_penilaian ?? 0),
                'rata_rata' => $rataRata,
                'nilai_akhir_rata' => $agregat !== null ? round((float) $agregat->nilai_akhir_rata, 2) : null,
                'lulus_aktual' => $lulus,
                'status_ditentukan' => $ditentukan,
                'persentase_kelulusan' => $ditentukan > 0 ? round($lulus / $ditentukan * 100, 2) : null,
            ];
        })->values()->all();

        $jumlahPenilaian = array_sum(array_column($tren, 'jumlah_penilaian'));
        $jumlahLulusAktual = array_sum(array_column($tren, 'lulus_aktual'));
        $jumlahStatusDitentukan = array_sum(array_column($tren, 'status_ditentukan'));
        $akademik = [
            'dinilai' => $jumlahPenilaian,
            'belum_dinilai' => $jumlahPeserta - $jumlahPenilaian,
            'status_ditentukan' => $jumlahStatusDitentukan,
            'lulus' => $jumlahLulusAktual,
            'tidak_lulus' => $jumlahStatusDitentukan - $jumlahLulusAktual,
            'belum_ditentukan' => $jumlahPenilaian - $jumlahStatusDitentukan,
        ];

        $featureImportance = [];
        foreach (PenilaianPkpa::FEATURE_LABELS as $feature => $label) {
            $value = $modelAktif?->feature_importance_json[$feature] ?? null;
            $featureImportance[] = [
                'fitur' => $feature,
                'label' => $label,
                'persentase' => is_numeric($value) ? round((float) $value * 100, 2) : null,
            ];
        }
        $evaluasi = [];
        foreach (['akurasi' => 'Akurasi', 'presisi' => 'Presisi', 'recall' => 'Recall', 'f1_score' => 'F1-Score'] as $metric => $label) {
            $evaluasi[] = ['label' => $label, 'nilai' => $modelAktif !== null ? (float) $modelAktif->{$metric} : null];
        }
        $confusionMatrix = $modelAktif?->confusion_matrix_json;
        $chartData = [
            'akademik' => [
                'labels' => array_column($tren, 'angkatan'),
                'values' => array_column($tren, 'nilai_akhir_rata'),
                'counts' => array_column($tren, 'jumlah_penilaian'),
            ],
            'aktual' => [
                'labels' => ['Lulus', 'Tidak Lulus', 'Belum Ditentukan', 'Belum dinilai'],
                'values' => [$akademik['lulus'], $akademik['tidak_lulus'], $akademik['belum_ditentukan'], $akademik['belum_dinilai']],
            ],
            'prediksi' => [
                'labels' => ['Lulus', 'Berisiko Tidak Lulus'],
                'values' => [$lulus, $berisiko],
            ],
        ];

        return view('dashboard', [
            'angkatan' => $angkatan, 'angkatanTerpilih' => $angkatanTerpilih, 'ringkasan' => $ringkasan, 'akademik' => $akademik,
            'modelAktif' => $modelAktif, 'tren' => $tren, 'features' => PenilaianPkpa::FEATURE_LABELS,
            'featureImportance' => $featureImportance, 'evaluasi' => $evaluasi,
            'confusionMatrix' => $confusionMatrix, 'chartData' => $chartData,
            'riwayat' => (clone $prediksiTerbaru)->with(['peserta.angkatan', 'modelMl'])
                ->orderByDesc('tanggal_prediksi')->orderByDesc('id')->limit(5)->get(),
        ]);
    }

    /** @return Builder<HasilPrediksi> */
    private function latestPredictions(?int $angkatanId): Builder
    {
        return HasilPrediksi::where('id', function (QueryBuilder $query): void {
            $query->select('terbaru.id')->from('hasil_prediksi as terbaru')
                ->whereColumn('terbaru.peserta_id', 'hasil_prediksi.peserta_id')
                ->orderByDesc('terbaru.tanggal_prediksi')->orderByDesc('terbaru.id')->limit(1);
        })->when($angkatanId !== null, fn (Builder $query): Builder => $query->whereHas('peserta', fn (Builder $query): Builder => $query->where('angkatan_id', $angkatanId)));
    }
}
