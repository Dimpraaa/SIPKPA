<?php

namespace App\Http\Controllers;

use App\Models\AngkatanPkpa;
use App\Models\HasilPrediksi;
use App\Models\PenilaianPkpa;
use App\Services\AcademicExcelExporter;
use App\Services\AcademicReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPesertaController extends Controller
{
    public function __construct(public AcademicReportService $reports, public AcademicExcelExporter $excel) {}

    public function berisiko(Request $request): View
    {
        $data = $this->data($request, true);
        $data['features'] = array_map(fn (string $label): string => str_replace(' (%)', '', $label), PenilaianPkpa::FEATURE_LABELS);
        $data['rows'] = array_map(function (array $row) use ($data): array {
            $row['indikator'] = array_keys(array_filter($data['standards'], fn (int $minimum, string $key): bool => $row[$key] !== null && $row[$key] < $minimum, ARRAY_FILTER_USE_BOTH));
            $row['rekomendasi_tampilan'] = $row['rekomendasi'] !== null ? str_ireplace('standar minimal', 'ambang evaluasi simulasi', $row['rekomendasi']) : null;

            return $row;
        }, $data['rows']);

        return view('laporan.berisiko', $data);
    }

    public function rekap(Request $request): View
    {
        $data = $this->data($request, false);
        $aggregates = PenilaianPkpa::join('peserta_pkpa', 'peserta_pkpa.id', '=', 'penilaian_pkpa.peserta_id')
            ->select('peserta_pkpa.angkatan_id')
            ->selectRaw('COUNT(*) as dinilai, AVG(nilai_akhir_rata) as rata_rata')
            ->selectRaw('SUM(CASE WHEN status_aktual = ? THEN 1 ELSE 0 END) as lulus', ['Lulus'])
            ->selectRaw('SUM(CASE WHEN status_aktual IN (?, ?) THEN 1 ELSE 0 END) as ditentukan', ['Lulus', 'Tidak Lulus'])
            ->groupBy('peserta_pkpa.angkatan_id')->get()->keyBy('angkatan_id');
        $data['comparison'] = AngkatanPkpa::withCount('peserta')->orderBy('tanggal_mulai')->orderBy('id')->get()
            ->map(function (AngkatanPkpa $angkatan) use ($aggregates): array {
                $aggregate = $aggregates->get($angkatan->id);

                return [
                    'id' => $angkatan->id, 'nama' => $angkatan->nama_angkatan,
                    'peserta' => $angkatan->peserta_count, 'dinilai' => (int) ($aggregate?->dinilai ?? 0),
                    'rata_rata' => $aggregate !== null ? round((float) $aggregate->rata_rata, 2) : null,
                    'lulus' => (int) ($aggregate?->lulus ?? 0), 'ditentukan' => (int) ($aggregate?->ditentukan ?? 0),
                ];
            })->all();
        $data['comparisonChart'] = [
            'labels' => array_column($data['comparison'], 'nama'),
            'values' => array_column($data['comparison'], 'rata_rata'),
            'counts' => array_column($data['comparison'], 'dinilai'),
            'selected' => $data['selected']?->id,
            'ids' => array_column($data['comparison'], 'id'),
        ];

        return view('laporan.peserta', $data);
    }

    public function exportBerisiko(Request $request, string $format): Response|StreamedResponse
    {
        return $this->export($request, true, $format);
    }

    public function exportRekap(Request $request, string $format): Response|StreamedResponse
    {
        return $this->export($request, false, $format);
    }

    private function export(Request $request, bool $risiko, string $format): Response|StreamedResponse
    {
        $data = $this->data($request, $risiko, true);
        $filename = ($risiko ? 'peringatan-dini-pkpa-' : 'rekap-penilaian-pkpa-').now()->format('Ymd-His');
        if ($format === 'excel') {
            return $this->excel->download($filename.'.xlsx', $data['title'], $data['columns'], $data['rows'], $data['standards']);
        }

        return Pdf::loadView('laporan.pdf.peserta', $data)->setPaper('a4', 'landscape')
            ->setOption(['isRemoteEnabled' => false, 'isJavascriptEnabled' => false])->download($filename.'.pdf');
    }

    /** @return array<string, mixed> */
    private function data(Request $request, bool $risiko, bool $export = false): array
    {
        $filters = $request->validate([
            'angkatan_id' => ['nullable', 'integer', 'exists:angkatan_pkpa,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'min_gagal' => ['nullable', 'numeric', 'between:0,100'],
            ...($risiko ? ['urutan' => ['nullable', 'in:nama,estimasi_tertinggi']] : []),
        ]);
        $angkatan = AngkatanPkpa::orderByDesc('tanggal_mulai')->orderByDesc('id')->get();
        $selected = isset($filters['angkatan_id']) ? $angkatan->firstWhere('id', (int) $filters['angkatan_id']) : ($risiko ? null : $angkatan->first());
        $filters['angkatan_id'] = $selected?->id;
        $query = $this->reports->participants($filters, $risiko);
        if ($risiko && ($filters['urutan'] ?? 'nama') === 'estimasi_tertinggi') {
            $query->reorder()->orderByDesc(HasilPrediksi::select('probabilitas_gagal')
                ->whereColumn('peserta_id', 'peserta_pkpa.id')
                ->orderByDesc('tanggal_prediksi')->orderByDesc('id')->limit(1))
                ->orderBy('nama_lengkap')->orderBy('id');
        }
        if (! $risiko && $selected === null) {
            $query->whereRaw('1 = 0');
        }
        $pagination = $export ? null : $query->paginate(20)->withQueryString();
        $participants = $export ? $query->get() : $pagination->getCollection();
        $rows = $this->reports->rows($participants, $pagination ? ($pagination->currentPage() - 1) * $pagination->perPage() : 0);
        $columns = AcademicReportService::columns($risiko);
        $pdfColumns = array_diff_key($columns, array_flip(['model', 'tanggal_prediksi', 'rekomendasi']));

        return [
            'title' => $risiko ? 'Pemantauan Peserta Berisiko (Early Warning)' : 'Rekapitulasi Angkatan',
            'description' => $risiko
                ? 'Daftar peserta yang memerlukan pembinaan akademik atau remedial sebelum UPA.'
                : 'Rekap nilai, status kelulusan, dan prediksi terbaru peserta per angkatan.',
            'risiko' => $risiko,
            'angkatan' => $angkatan, 'selected' => $selected, 'filters' => $filters,
            'rows' => $rows, 'columns' => $columns, 'pdfColumns' => $pdfColumns,
            'pagination' => $pagination, 'total' => $pagination?->total() ?? count($rows),
            'standards' => AcademicReportService::standards(),
            'printedAt' => now(), 'printedBy' => $request->user()->nama_lengkap,
        ];
    }
}
