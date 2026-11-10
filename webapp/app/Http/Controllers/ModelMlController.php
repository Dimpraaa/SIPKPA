<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrainModelRequest;
use App\Models\ModelMl;
use App\Models\PenilaianPkpa;
use App\Services\RandomForestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModelMlController extends Controller
{
    public function index(): View
    {
        return view('model.index', [
            'models' => ModelMl::with('pembuat')->latest('id')->paginate(10),
            'modelAktif' => ModelMl::where('is_active', true)->first(),
            'jumlahHistoris' => PenilaianPkpa::whereIn('status_aktual', ['Lulus', 'Tidak Lulus'])->count(),
            'features' => PenilaianPkpa::FEATURE_LABELS,
        ]);
    }

    public function store(TrainModelRequest $request, RandomForestService $service): RedirectResponse
    {
        $records = PenilaianPkpa::whereIn('status_aktual', ['Lulus', 'Tidak Lulus'])->orderBy('id')->get();
        $counts = $records->countBy('status_aktual');
        if ($records->count() < 10 || ($counts['Lulus'] ?? 0) < 2 || ($counts['Tidak Lulus'] ?? 0) < 2) {
            throw ValidationException::withMessages(['ml' => 'Pelatihan membutuhkan minimal 10 data historis dengan sedikitnya 2 peserta pada masing-masing kelas Lulus dan Tidak Lulus.']);
        }
        $parameters = [
            'n_estimators' => $request->integer('n_estimators'),
            'max_depth' => $request->filled('max_depth') ? $request->integer('max_depth') : null,
            'criterion' => 'gini',
        ];
        $data = $records->map(function (PenilaianPkpa $record): array {
            $row = [];
            foreach (array_keys(PenilaianPkpa::FEATURE_LABELS) as $feature) {
                $row[$feature] = (float) $record->{$feature};
            }

            return [...$row, 'status_aktual' => $record->status_aktual];
        })->all();
        $result = $service->train($data, $parameters);
        ModelMl::create([
            ...$parameters,
            'dibuat_oleh' => $request->user()->id,
            'nama_versi' => $request->validated('nama_versi'),
            'jumlah_data_latih' => $result['jumlah_data_latih'],
            'jumlah_data_uji' => $result['jumlah_data_uji'],
            'akurasi' => $result['akurasi'], 'presisi' => $result['presisi'],
            'recall' => $result['recall'], 'f1_score' => $result['f1_score'],
            'confusion_matrix_json' => $result['confusion_matrix'],
            'feature_importance_json' => $result['feature_importance'],
            'file_path_model' => $result['file_path_model'],
            'is_active' => false,
        ]);

        return to_route('model.index')->with('success', 'Model berhasil dilatih dan disimpan. Pilih Aktifkan untuk menggunakannya pada prediksi.');
    }

    public function activate(ModelMl $model): RedirectResponse
    {
        DB::transaction(function () use ($model): void {
            ModelMl::orderBy('id')->lockForUpdate()->get();
            ModelMl::where('is_active', true)->update(['is_active' => false]);
            $model->update(['is_active' => true]);
        });

        return to_route('model.index')->with('success', 'Model '.$model->nama_versi.' sekarang aktif.');
    }
}
