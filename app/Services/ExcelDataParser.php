<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelDataParser
{
    public function __construct(private ProfileColumnMapper $columnMapper = new ProfileColumnMapper) {}

    /**
     * Membaca file Excel dan mengembalikan header kolom serta baris data.
     *
     * @param  string  $filePath  Path lengkap file Excel di storage
     * @return array [ 'headers' => [...], 'rows' => [...] ]
     */
    public function parse(string $filePath, ?string $profileSlug = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, false);

        if (empty($rawRows)) {
            return [
                'headers' => [],
                'rows' => [],
                'header_row_index' => null,
            ];
        }

        $headerRowIndex = $profileSlug === null
            ? 0
            : $this->columnMapper->detectHeaderRow($profileSlug, $rawRows);

        if ($headerRowIndex === null) {
            return [
                'headers' => [],
                'rows' => [],
                'header_row_index' => null,
            ];
        }

        $rawHeaders = $rawRows[$headerRowIndex];
        $headers = [];
        foreach ($rawHeaders as $idx => $headerText) {
            $name = trim((string) $headerText);
            $headers[$idx] = $name !== '' ? $name : 'Kolom_'.($idx + 1);
        }

        $headers = array_values($headers);
        $cleanRows = [];
        foreach ($rawRows as $rowIndex => $row) {
            if ($rowIndex <= $headerRowIndex) {
                continue;
            }

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
            'header_row_index' => $headerRowIndex,
        ];
    }
}
