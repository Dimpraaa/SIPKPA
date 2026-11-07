<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPenilaianRequest;
use App\Models\AngkatanPkpa;
use App\Services\PenilaianImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenilaianImportController extends Controller
{
    public function create(): View
    {
        return view('peserta.import', ['angkatan' => AngkatanPkpa::orderByDesc('tanggal_mulai')->get()]);
    }

    public function store(ImportPenilaianRequest $request, PenilaianImportService $service): RedirectResponse
    {
        $angkatan = AngkatanPkpa::findOrFail($request->integer('angkatan_id'));
        $count = $service->import($request->file('file'), $angkatan);

        return to_route('peserta.index', ['angkatan_id' => $angkatan->id])->with('success', $count.' penilaian berhasil diimport.');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, PenilaianImportService::headers(), ',', '"', '');
            fclose($stream);
        }, 'template-penilaian-pkpa.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
