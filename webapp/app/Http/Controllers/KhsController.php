<?php

namespace App\Http\Controllers;

use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Services\AcademicReportService;
use App\Services\RadarChartService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KhsController extends Controller
{
    public function __invoke(Request $request, PesertaPkpa $peserta, AcademicReportService $reports, RadarChartService $radar): Response
    {
        $peserta->load(['angkatan', 'penilaian', 'prediksiTerbaru.modelMl']);
        abort_unless($peserta->penilaian, 422, 'Penilaian peserta belum tersedia untuk mencetak KHS.');
        $data = $reports->radarData($peserta);

        return Pdf::loadView('laporan.pdf.khs', [
            'title' => 'Kartu Hasil Studi & Evaluasi Individual', 'kode' => 'LUA-07',
            'peserta' => $peserta, 'features' => PenilaianPkpa::FEATURE_LABELS,
            'standards' => AcademicReportService::standards(), 'radarImage' => $radar->pngDataUri($data['values'], $data['standards']),
            'row' => $reports->rows(collect([$peserta]))[0],
            'printedAt' => now(), 'printedBy' => $request->user()->nama_lengkap,
        ])->setPaper('a4', 'portrait')->setOption(['isRemoteEnabled' => false, 'isJavascriptEnabled' => false])
            ->download('khs-pkpa-'.$peserta->id.'-'.now()->format('Ymd').'.pdf');
    }
}
