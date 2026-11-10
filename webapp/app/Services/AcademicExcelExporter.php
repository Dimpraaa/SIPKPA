<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcademicExcelExporter
{
    /** @param array<string, string> $columns
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, int>  $standards
     */
    public function download(string $filename, string $title, array $columns, array $rows, array $standards): StreamedResponse
    {
        return response()->streamDownload(function () use ($title, $columns, $rows, $standards): void {
            $book = new Spreadsheet;
            try {
                $book->getProperties()->setCreator('Advokat Propindo')->setTitle($title)->setCompany('Advokat Propindo');
                $sheet = $book->getActiveSheet()->setTitle('Laporan PKPA');
                $lastColumn = Coordinate::stringFromColumnIndex(count($columns));
                $sheet->fromArray(array_values($columns), null, 'A1');
                foreach ($rows as $index => $row) {
                    foreach (array_keys($columns) as $columnIndex => $key) {
                        $cell = Coordinate::stringFromColumnIndex($columnIndex + 1).($index + 2);
                        $value = $row[$key] ?? null;
                        if (is_int($value) || is_float($value)) {
                            $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_NUMERIC);
                            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($key === 'no' ? '0' : (in_array($key, ['probabilitas_lulus', 'probabilitas_gagal', 'persen_kehadiran'], true) ? '0.00"%"' : '0.00'));
                            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        } else {
                            $sheet->setCellValueExplicit($cell, $value ?? '—', DataType::TYPE_STRING);
                        }
                        if (isset($standards[$key]) && $value !== null && $value < $standards[$key]) {
                            $sheet->getStyle($cell)->applyFromArray([
                                'font' => ['color' => ['rgb' => 'B91C1C'], 'bold' => true],
                            ]);
                        }
                    }
                }
                $lastRow = count($rows) + 1;
                $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '115E59']],
                ]);
                $sheet->getStyle('A1:'.$lastColumn.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('A1:'.$lastColumn.'1')->getAlignment()->setWrapText(true);
                foreach (array_keys($columns) as $index => $key) {
                    $column = Coordinate::stringFromColumnIndex($index + 1);
                    $sheet->getColumnDimension($column)->setWidth(match ($key) {
                        'no' => 6, 'nama_lengkap', 'model' => 28, 'rekomendasi' => 70, default => 19,
                    });
                    if (in_array($key, ['nomor_peserta', 'nama_lengkap', 'angkatan', 'model', 'tanggal_prediksi'], true)) {
                        $sheet->getColumnDimension($column)->setAutoSize(true);
                    }
                    if ($key === 'rekomendasi' && $lastRow > 1) {
                        $sheet->getStyle($column.'2:'.$column.$lastRow)->getAlignment()->setWrapText(true);
                    }
                }
                $sheet->freezePane('E2');
                $sheet->setAutoFilter('A1:'.$lastColumn.$lastRow);
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0)->setRowsToRepeatAtTopByStartAndEnd(1, 1);
                $sheet->getHeaderFooter()->setOddHeader('&CAdvokat Propindo - '.$title)->setOddFooter('&L'.now()->format('d/m/Y H:i').'&RHalaman &P / &N');
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store']);
    }
}
