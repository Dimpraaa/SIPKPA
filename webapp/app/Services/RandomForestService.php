<?php

namespace App\Services;

use App\Models\PenilaianPkpa;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RandomForestService
{
    /** @param list<array<string, mixed>> $data
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function train(array $data, array $parameters): array
    {
        $result = $this->post('/api/train', ['data' => $data, ...$parameters, 'test_size' => 0.2, 'random_state' => 42]);
        $rules = [
            'file_path_model' => ['required', 'string', 'max:255', 'regex:~^models/rf_model_[a-zA-Z0-9_]+\.pkl$~'],
            'jumlah_data_latih' => ['required', 'integer', 'min:2'],
            'jumlah_data_uji' => ['required', 'integer', 'min:2'],
            'confusion_matrix' => ['required', 'array:TP,TN,FP,FN'],
            'feature_importance' => ['required', 'array'],
        ];
        foreach (['akurasi', 'presisi', 'recall', 'f1_score'] as $metric) {
            $rules[$metric] = ['required', 'numeric', 'between:0,100'];
        }
        foreach (['TP', 'TN', 'FP', 'FN'] as $cell) {
            $rules['confusion_matrix.'.$cell] = ['required', 'integer', 'min:0'];
        }
        foreach (array_keys(PenilaianPkpa::FEATURE_LABELS) as $feature) {
            $rules['feature_importance.'.$feature] = ['required', 'numeric', 'between:0,1'];
        }
        $this->validateResponse($result, $rules);
        if (count($data) !== $result['jumlah_data_latih'] + $result['jumlah_data_uji']
            || array_sum($result['confusion_matrix']) !== $result['jumlah_data_uji']) {
            $this->invalidResponse();
        }

        return $result;
    }

    /** @param list<array<string, float>> $data
     * @return list<array<string, mixed>>
     */
    public function predict(string $modelPath, array $data): array
    {
        $result = $this->post('/api/predict', ['file_path_model' => $modelPath, 'data' => $data]);
        $this->validateResponse($result, [
            'file_path_model' => ['required', 'string'],
            'hasil_prediksi' => ['required', 'array', 'size:'.count($data)],
            'hasil_prediksi.*.status_prediksi' => ['required', 'in:Lulus,Berisiko Tidak Lulus'],
            'hasil_prediksi.*.probabilitas_lulus' => ['required', 'numeric', 'between:0,100'],
            'hasil_prediksi.*.probabilitas_gagal' => ['required', 'numeric', 'between:0,100'],
            'hasil_prediksi.*.rekomendasi_tindak_lanjut' => ['required', 'string', 'max:10000'],
        ]);
        if ($result['file_path_model'] !== $modelPath || ! array_is_list($result['hasil_prediksi'])) {
            $this->invalidResponse();
        }
        foreach ($result['hasil_prediksi'] as $row) {
            if (abs($row['probabilitas_lulus'] + $row['probabilitas_gagal'] - 100) > 0.02) {
                $this->invalidResponse();
            }
        }

        return $result['hasil_prediksi'];
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $payload): array
    {
        try {
            $response = Http::baseUrl(rtrim(config('services.ml.url'), '/'))->acceptJson()
                ->connectTimeout(3)->timeout((int) config('services.ml.timeout', 180))
                ->post($endpoint, $payload);
        } catch (ConnectionException $exception) {
            throw ValidationException::withMessages(['ml' => 'Layanan Python belum menyala atau tidak dapat dijangkau. Jalankan FastAPI pada port 8001, lalu coba kembali.']);
        }
        if (! $response->successful()) {
            $detail = $response->json('detail');
            $message = is_string($detail) ? $detail : 'Periksa kelengkapan data dan parameter pelatihan.';
            throw ValidationException::withMessages(['ml' => 'Layanan Random Forest menolak permintaan (HTTP '.$response->status().'). '.$message]);
        }
        $result = $response->json();
        if (! is_array($result)) {
            $this->invalidResponse();
        }

        return $result;
    }

    /** @param array<string, mixed> $result
     * @param  array<string, list<string>>  $rules
     */
    private function validateResponse(array $result, array $rules): void
    {
        if (Validator::make($result, $rules)->fails()) {
            $this->invalidResponse();
        }
    }

    private function invalidResponse(): never
    {
        throw ValidationException::withMessages(['ml' => 'Respons layanan Random Forest tidak lengkap atau tidak konsisten. Data belum disimpan; silakan coba kembali.']);
    }
}
