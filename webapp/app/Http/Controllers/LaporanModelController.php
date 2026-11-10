<?php

namespace App\Http\Controllers;

use App\Models\ModelMl;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LaporanModelController extends Controller
{
    public function index(Request $request): View
    {
        return view('laporan.model', $this->data($request));
    }

    public function pdf(Request $request): Response
    {
        return Pdf::loadView('laporan.pdf.model', $this->data($request, true))->setPaper('a4', 'landscape')
            ->setOption(['isRemoteEnabled' => false, 'isJavascriptEnabled' => false])->download('riwayat-pengujian-random-forest-'.now()->format('Ymd-His').'.pdf');
    }

    /** @return array<string, mixed> */
    private function data(Request $request, bool $export = false): array
    {
        $filters = $request->validate(['criterion' => ['nullable', 'in:gini,entropy']]);
        $query = ModelMl::with('pembuat')->when($filters['criterion'] ?? null, fn (Builder $query, string $criterion): Builder => $query->where('criterion', $criterion))->latest('id');

        return [
            'title' => 'Riwayat Evaluasi Model',
            'models' => $export ? $query->get() : $query->paginate(20)->withQueryString(),
            'export' => $export, 'filters' => $filters, 'printedAt' => now(), 'printedBy' => $request->user()->nama_lengkap,
        ];
    }
}
