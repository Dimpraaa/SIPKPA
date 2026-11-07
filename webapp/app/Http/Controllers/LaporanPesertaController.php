<?php

namespace App\Http\Controllers;

use App\Models\AngkatanPkpa;
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
        return view('laporan.peserta', $this->data($request, true));
    }

    public function rekap(Request $request): View
    {
        return view('laporan.peserta', $this->data($request, false));
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
        ]);
        $angkatan = AngkatanPkpa::orderByDesc('tanggal_mulai')->orderByDesc('id')->get();
        $selected = isset($filters['angkatan_id']) ? $angkatan->firstWhere('id', (int) $filters['angkatan_id']) : ($risiko ? null : $angkatan->first());
        $filters['angkatan_id'] = $selected?->id;
        $query = $this->reports->participants($filters, $risiko);
        if (! $risiko && $selected === null) {
            $query->whereRaw('1 = 0');
        }
        $pagination = $export ? null : $query->paginate(20)->withQueryString();
        $participants = $export ? $query->get() : $pagination->getCollection();
        $rows = $this->reports->rows($participants, $pagination ? ($pagination->currentPage() - 1) * $pagination->perPage() : 0);
        $columns = AcademicReportService::columns($risiko);
        $pdfColumns = array_diff_key($columns, array_flip(['model', 'tanggal_prediksi', 'rekomendasi']));

        return [
            'title' => $risiko ? 'Laporan Peringatan Dini / Peserta Berisiko' : 'Rekapitulasi Penilaian & Prediksi Angkatan',
            'kode' => $risiko ? 'LUA-05' : 'LUA-06', 'risiko' => $risiko,
            'angkatan' => $angkatan, 'selected' => $selected, 'filters' => $filters,
            'rows' => $rows, 'columns' => $columns, 'pdfColumns' => $pdfColumns,
            'pagination' => $pagination, 'total' => $pagination?->total() ?? count($rows),
            'standards' => AcademicReportService::standards(),
            'printedAt' => now(), 'printedBy' => $request->user()->nama_lengkap,
        ];
    }
}
