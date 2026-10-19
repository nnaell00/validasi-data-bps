<?php

namespace App\Services;

use App\Models\ValidationBatch;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ValidationBatchExcelExporter
{
    public function createEmployeeSpreadsheet(ValidationBatch $batch): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $this->populateEmployeeSheet($spreadsheet->getActiveSheet(), $batch);
        $spreadsheet->getActiveSheet()->setTitle('Data Pegawai Hasil Perbaikan');

        return $spreadsheet;
    }

    public function createReportSpreadsheet(ValidationBatch $batch): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $employeeSheet = $spreadsheet->getActiveSheet();
        $this->populateEmployeeSheet($employeeSheet, $batch);
        $employeeSheet->setTitle('Data Pegawai Hasil Perbaikan');

        $auditSheet = $spreadsheet->createSheet();
        $auditSheet->setTitle('Rekapitulasi Audit Trail');
        $this->populateAuditSheet($auditSheet, $batch);

        return $spreadsheet;
    }

    private function populateEmployeeSheet(Worksheet $sheet, ValidationBatch $batch): void
    {
        $headers = $batch->headers ?? [];

        foreach ($headers as $columnIndex => $header) {
            $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).'1';
            $this->setCellValue($sheet, $coordinate, $header, true);
        }

        foreach ($batch->rows()->orderBy('row_number')->get() as $rowOffset => $row) {
            $excelRow = $rowOffset + 2;

            foreach (array_keys($headers) as $columnIndex) {
                $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).$excelRow;
                $value = in_array($columnIndex, ValidationBatch::PRESERVED_SOURCE_COLUMN_INDICES, true)
                    ? ($row->original_data[$columnIndex] ?? null)
                    : ($row->current_data[$columnIndex] ?? null);
                $isIdentifier = in_array($columnIndex, [2, 11], true);

                $this->setCellValue($sheet, $coordinate, $value, $isIdentifier);
            }
        }
    }

    private function populateAuditSheet(Worksheet $sheet, ValidationBatch $batch): void
    {
        $headers = [
            'Waktu Perubahan',
            'Petugas',
            'Baris Data',
            'Nama Kolom',
            'Nilai Sebelum Perbaikan',
            'Nilai Setelah Perbaikan',
            'Jenis Aktivitas',
            'Catatan',
        ];

        foreach ($headers as $columnIndex => $header) {
            $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).'1';
            $this->setCellValue($sheet, $coordinate, $header, true);
        }

        $auditLogs = $batch->auditLogs()
            ->with(['user', 'row'])
            ->reorder('created_at')
            ->orderBy('id')
            ->get();

        foreach ($auditLogs as $rowOffset => $auditLog) {
            $values = [
                $auditLog->created_at === null
                    ? ''
                    : $auditLog->created_at->copy()->timezone(config('app.display_timezone'))->format('d/m/Y H:i').' WIB',
                $auditLog->user?->name ?? '',
                $auditLog->row?->row_number === null ? '' : (string) $auditLog->row->row_number,
                $auditLog->column_name ?? '',
                $auditLog->old_value ?? '',
                $auditLog->new_value ?? '',
                $auditLog->action,
                $auditLog->notes ?? '',
            ];
            $excelRow = $rowOffset + 2;

            foreach ($values as $columnIndex => $value) {
                $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).$excelRow;
                $this->setCellValue($sheet, $coordinate, $value, true);
            }
        }
    }

    private function setCellValue(Worksheet $sheet, string $coordinate, mixed $value, bool $asText = false): void
    {
        if ($asText || is_string($value)) {
            $sheet->setCellValueExplicit(
                $coordinate,
                $value === null ? '' : (string) $value,
                DataType::TYPE_STRING,
            );

            return;
        }

        $sheet->setCellValue($coordinate, $value);
    }
}
