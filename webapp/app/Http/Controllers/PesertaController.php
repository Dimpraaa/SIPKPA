<?php

namespace App\Http\Controllers;

use App\Http\Requests\PesertaRequest;
use App\Models\AngkatanPkpa;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Services\AcademicReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PesertaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'angkatan_id' => ['nullable', 'integer', 'exists:angkatan_pkpa,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'status_aktual' => ['nullable', 'in:Lulus,Tidak Lulus,Belum Ditentukan,belum_dinilai'],
        ]);
        $peserta = PesertaPkpa::with(['angkatan', 'penilaian'])
            ->when($filters['angkatan_id'] ?? null, fn ($query, $id) => $query->where('angkatan_id', $id))
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($query) => $query->where('nama_lengkap', 'like', '%'.$q.'%')->orWhere('nomor_peserta', 'like', '%'.$q.'%')))
            ->when($filters['status_aktual'] ?? null, fn ($query, $status) => $status === 'belum_dinilai'
                ? $query->whereDoesntHave('penilaian')
                : $query->whereRelation('penilaian', 'status_aktual', $status))
            ->orderBy('nama_lengkap')->paginate(20)->withQueryString();

        return view('peserta.index', [
            'peserta' => $peserta, 'angkatan' => AngkatanPkpa::orderByDesc('tanggal_mulai')->get(),
            'standards' => AcademicReportService::standards(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new PesertaPkpa);
    }

    public function edit(PesertaPkpa $peserta): View
    {
        $peserta->load('penilaian');

        return $this->form($peserta);
    }

    private function form(PesertaPkpa $peserta): View
    {
        return view('peserta.form', ['peserta' => $peserta, 'angkatan' => AngkatanPkpa::orderByDesc('tanggal_mulai')->get(), 'features' => PenilaianPkpa::FEATURE_LABELS]);
    }

    public function store(PesertaRequest $request): RedirectResponse
    {
        $peserta = DB::transaction(function () use ($request): PesertaPkpa {
            $peserta = PesertaPkpa::create(Arr::only($request->validated(), (new PesertaPkpa)->getFillable()));
            $peserta->penilaian()->create(Arr::only($request->validated(), array_keys(PenilaianPkpa::nilaiRules())));

            return $peserta;
        });

        return to_route('peserta.show', $peserta)->with('success', 'Peserta dan penilaian berhasil ditambahkan.');
    }

    public function update(PesertaRequest $request, PesertaPkpa $peserta): RedirectResponse
    {
        DB::transaction(function () use ($request, $peserta): void {
            $peserta = PesertaPkpa::whereKey($peserta->id)->lockForUpdate()->firstOrFail();
            $peserta->update(Arr::only($request->validated(), $peserta->getFillable()));
            $peserta->penilaian()->updateOrCreate(['peserta_id' => $peserta->id], Arr::only($request->validated(), array_keys(PenilaianPkpa::nilaiRules())));
        });

        return to_route('peserta.show', $peserta)->with('success', 'Data peserta dan penilaian berhasil diperbarui.');
    }

    public function show(PesertaPkpa $peserta, AcademicReportService $reports): View
    {
        $peserta->load(['angkatan', 'penilaian', 'prediksiTerbaru.modelMl']);

        return view('peserta.show', [
            'peserta' => $peserta, 'features' => PenilaianPkpa::FEATURE_LABELS,
            'standards' => AcademicReportService::standards(), 'radarData' => $reports->radarData($peserta),
            'riwayat' => $peserta->hasilPrediksi()->with(['modelMl', 'prediktor'])->orderByDesc('tanggal_prediksi')->orderByDesc('id')->paginate(10),
        ]);
    }

    public function destroy(PesertaPkpa $peserta): RedirectResponse
    {
        DB::transaction(function () use ($peserta): void {
            $peserta = PesertaPkpa::whereKey($peserta->id)->lockForUpdate()->firstOrFail();
            if ($peserta->hasilPrediksi()->exists()) {
                throw ValidationException::withMessages(['peserta' => 'Peserta yang memiliki riwayat prediksi tidak dapat dihapus.']);
            }
            $peserta->penilaian()->delete();
            $peserta->delete();
        });

        return to_route('peserta.index')->with('success', 'Peserta beserta penilaiannya berhasil dihapus.');
    }
}
