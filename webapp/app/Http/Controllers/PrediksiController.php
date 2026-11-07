<?php

namespace App\Http\Controllers;

use App\Models\AngkatanPkpa;
use App\Models\HasilPrediksi;
use App\Models\ModelMl;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use App\Services\RandomForestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrediksiController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['angkatan_id' => ['nullable', 'integer', 'exists:angkatan_pkpa,id']]);

        return view('prediksi.index', [
            'angkatan' => AngkatanPkpa::withCount('peserta')->orderByDesc('tanggal_mulai')->get(),
            'modelAktif' => ModelMl::where('is_active', true)->first(),
            'riwayat' => HasilPrediksi::with(['peserta.angkatan', 'modelMl', 'prediktor'])
                ->when($filters['angkatan_id'] ?? null, fn ($query, $id) => $query->whereHas('peserta', fn ($query) => $query->where('angkatan_id', $id)))
                ->latest('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function individual(Request $request, PesertaPkpa $peserta, RandomForestService $service): RedirectResponse
    {
        $this->execute(collect([$peserta->load('penilaian')]), $request->user()->id, $service);

        return to_route('peserta.show', $peserta)->with('success', 'Prediksi individual berhasil disimpan.');
    }

    public function angkatan(Request $request, AngkatanPkpa $angkatan, RandomForestService $service): RedirectResponse
    {
        if ($angkatan->status_angkatan !== 'berjalan') {
            throw ValidationException::withMessages(['angkatan' => 'Prediksi massal hanya tersedia untuk angkatan yang berjalan.']);
        }
        $peserta = $angkatan->peserta()->with('penilaian')->orderBy('id')->get();
        $this->execute($peserta, $request->user()->id, $service);

        return to_route('prediksi.index', ['angkatan_id' => $angkatan->id])->with('success', 'Prediksi '.$peserta->count().' peserta berhasil disimpan.');
    }

    /** @param Collection<int, PesertaPkpa> $peserta */
    private function execute(Collection $peserta, int $userId, RandomForestService $service): void
    {
        $model = ModelMl::where('is_active', true)->first();
        if (! $model) {
            throw ValidationException::withMessages(['ml' => 'Belum ada model aktif. Latih dan aktifkan model melalui menu Eksperimen Model.']);
        }
        if ($peserta->isEmpty() || $peserta->contains(fn (PesertaPkpa $row): bool => $row->penilaian === null)) {
            throw ValidationException::withMessages(['peserta' => 'Pastikan terdapat peserta dan seluruh peserta telah memiliki enam komponen penilaian.']);
        }
        $snapshots = $peserta->map(fn (PesertaPkpa $row): array => $this->features($row->penilaian))->values()->all();
        $results = $service->predict($model->file_path_model, $snapshots);
        DB::transaction(function () use ($peserta, $snapshots, $results, $model, $userId): void {
            $lockedParticipants = PesertaPkpa::whereIn('id', $peserta->pluck('id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $locked = PenilaianPkpa::whereIn('id', $peserta->pluck('penilaian.id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($peserta->values() as $index => $row) {
                $nilai = $locked->get($row->penilaian->id);
                if (! $lockedParticipants->has($row->id) || ! $nilai || $this->features($nilai) !== $snapshots[$index]) {
                    throw ValidationException::withMessages(['ml' => 'Data peserta berubah saat prediksi berlangsung. Jalankan kembali agar hasil sesuai nilai terbaru.']);
                }
                HasilPrediksi::create([
                    ...Arr::only($results[$index], ['status_prediksi', 'probabilitas_lulus', 'probabilitas_gagal', 'rekomendasi_tindak_lanjut']),
                    'peserta_id' => $row->id, 'penilaian_id' => $nilai->id,
                    'model_id' => $model->id, 'diprediksi_oleh' => $userId, 'tanggal_prediksi' => now(),
                ]);
            }
        });
    }

    /** @return array<string, float> */
    private function features(PenilaianPkpa $nilai): array
    {
        $features = [];
        foreach (array_keys(PenilaianPkpa::FEATURE_LABELS) as $feature) {
            $features[$feature] = (float) $nilai->{$feature};
        }

        return $features;
    }
}
