<?php

namespace App\Services;

use App\Models\AngkatanPkpa;
use App\Models\PenilaianPkpa;
use App\Models\PesertaPkpa;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PenilaianImportService
{
    /** @return list<string> */
    public static function headers(): array
    {
        return ['nomor_peserta', ...array_keys(PenilaianPkpa::FEATURE_LABELS), 'status_aktual'];
    }

    public function import(UploadedFile $file, AngkatanPkpa $angkatan): int
    {
        $rows = $this->readRows($file);
        $header = array_map(fn (mixed $value): string => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"), array_shift($rows) ?? []);
        if (count($header) !== count(self::headers()) || array_diff(self::headers(), $header) || count(array_unique($header)) !== count($header)) {
            throw ValidationException::withMessages(['file' => 'Judul kolom tidak sesuai. Gunakan template CSV yang disediakan.']);
        }
        $validatedRows = [];
        $errors = [];
        $seen = [];
        foreach ($rows as $index => $row) {
            if (count(array_filter($row, fn (mixed $value): bool => $value !== null && trim((string) $value) !== '')) === 0) {
                continue;
            }
            $line = $index + 2;
            if (count($row) !== count($header)) {
                $errors['file'][] = 'Baris '.$line.': jumlah kolom tidak sesuai template.';

                continue;
            }
            $values = array_combine($header, array_map(fn (mixed $value): string => trim((string) $value), $row));
            $validator = Validator::make($values, ['nomor_peserta' => ['required', 'string', 'max:255'], ...PenilaianPkpa::nilaiRules()]);
            if ($validator->fails()) {
                $errors['file'][] = 'Baris '.$line.': '.implode(' ', $validator->errors()->all());

                continue;
            }
            if (isset($seen[$values['nomor_peserta']])) {
                $errors['file'][] = 'Baris '.$line.': nomor peserta berulang dalam berkas.';

                continue;
            }
            $seen[$values['nomor_peserta']] = true;
            $validatedRows[$line] = $validator->validated();
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
        if (! $validatedRows) {
            throw ValidationException::withMessages(['file' => 'Berkas tidak memuat baris penilaian.']);
        }

        return DB::transaction(function () use ($validatedRows, $angkatan): int {
            $peserta = PesertaPkpa::where('angkatan_id', $angkatan->id)
                ->whereIn('nomor_peserta', array_column($validatedRows, 'nomor_peserta'))->orderBy('id')->lockForUpdate()->get()->keyBy('nomor_peserta');
            foreach ($validatedRows as $line => $values) {
                if (! $peserta->has($values['nomor_peserta'])) {
                    throw ValidationException::withMessages(['file' => 'Baris '.$line.': peserta '.$values['nomor_peserta'].' tidak terdaftar pada angkatan yang dipilih.']);
                }
            }
            foreach ($validatedRows as $values) {
                $participant = $peserta->get($values['nomor_peserta']);
                unset($values['nomor_peserta']);
                $participant->penilaian()->updateOrCreate(['peserta_id' => $participant->id], $values);
            }

            return count($validatedRows);
        });
    }

    /** @return list<list<mixed>> */
    private function readRows(UploadedFile $file): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'csv') {
            $stream = fopen($file->getRealPath(), 'r');
            try {
                $first = fgets($stream);
                rewind($stream);
                $delimiter = substr_count($first ?: '', ';') > substr_count($first ?: '', ',') ? ';' : ',';
                $rows = [];
                while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                    $rows[] = $row;
                    if (count($rows) > 5001) {
                        throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris per import.']);
                    }
                }

                return $rows;
            } finally {
                fclose($stream);
            }
        }
        try {
            $reader = IOFactory::createReader(strtolower($file->getClientOriginalExtension()) === 'xlsx' ? 'Xlsx' : 'Xls');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            try {
                $sheet = $spreadsheet->getSheet(0);
                if ($sheet->getHighestDataRow() > 5001) {
                    throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris per import.']);
                }

                return $sheet->toArray(null, false, false, false);
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        } catch (SpreadsheetException $exception) {
            throw ValidationException::withMessages(['file' => 'Berkas Excel tidak dapat dibaca. Pastikan format dan isi berkas benar.']);
        }
    }
}
