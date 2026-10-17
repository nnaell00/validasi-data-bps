<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelDataParser
{
    /**
     * Membaca file Excel dan mengembalikan header kolom serta baris data.
     *
     * @param string $filePath Path lengkap file Excel di storage
     * @return array [ 'headers' => [...], 'rows' => [...] ]
     */
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, false);

        if (empty($rawRows)) {
            return [
                'headers' => [],
                'rows' => [],
            ];
        }

        // Baris ke-0 adalah Header Excel
        $rawHeaders = array_shift($rawRows);
        $headers = [];
        foreach ($rawHeaders as $idx => $headerText) {
            $name = trim((string) $headerText);
            $headers[] = $name !== '' ? $name : "Kolom_" . ($idx + 1);
        }

        // Saring baris yang benar-benar kosong
        $cleanRows = [];
        foreach ($rawRows as $row) {
            $allEmpty = true;
            foreach ($row as $val) {
                if ($val !== null && trim((string) $val) !== '') {
                    $allEmpty = false;
                    break;
                }
            }

            if (! $allEmpty) {
                $cleanRows[] = $row;
            }
        }

        return [
            'headers' => $headers,
            'rows' => $cleanRows,
        ];
    }
}
