<?php

namespace App\Http\Controllers;

use App\Models\ValidationBatch;
use App\Services\ValidationBatchExcelExporter;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ValidationExportController extends Controller
{
    public function data(ValidationBatch $batch, ValidationBatchExcelExporter $exporter): StreamedResponse
    {
        Gate::authorize('view', $batch);

        return $this->download(
            $exporter->createEmployeeSpreadsheet($batch),
            "data_pegawai_hasil_perbaikan_sesi_{$batch->id}.xlsx",
        );
    }

    public function report(ValidationBatch $batch, ValidationBatchExcelExporter $exporter): StreamedResponse
    {
        Gate::authorize('view', $batch);

        return $this->download(
            $exporter->createReportSpreadsheet($batch),
            "laporan_validasi_sesi_{$batch->id}.xlsx",
        );
    }

    private function download(Spreadsheet $spreadsheet, string $fileName): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
