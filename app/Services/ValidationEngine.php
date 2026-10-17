<?php

namespace App\Services;

use App\Models\ValidationProfile;
use App\Models\ValidationRule;
use Carbon\Carbon;
use DateTimeInterface;

class ValidationEngine
{
    /**
     * Memvalidasi kumpulan baris data berdasarkan aturan dari ValidationProfile.
     *
     * @param ValidationProfile $profile Profil validasi aktif
     * @param array $rows Kumpulan baris data mentah dari Excel
     * @return array Hasil analisis validasi dan daftar anomali
     */
    public function validate(ValidationProfile $profile, array $rows): array
    {
        $activeRules = $profile->activeRules;
        $anomalies = [];
        $anomaliesByRow = [];
        $rowsWithAnomalies = [];

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1; // Baris data manusiawi (1-based)

            foreach ($activeRules as $rule) {
                // Ambil nilai sel berdasarkan column_index atau key nama kolom
                $cellValue = $this->extractCellValue($row, $rule);
                $trimmedValue = is_string($cellValue) ? trim($cellValue) : $cellValue;
                $isEmpty = ($trimmedValue === null || $trimmedValue === '');

                // 1. Cek Kolom Wajib (is_required)
                if ($isEmpty) {
                    if ($rule->is_required) {
                        $this->recordAnomaly(
                            $anomalies,
                            $anomaliesByRow,
                            $rowsWithAnomalies,
                            $rowIndex,
                            $rowNumber,
                            $rule,
                            $cellValue,
                            $rule->error_message ?: "Kolom '{$rule->column_name}' wajib diisi (tidak boleh kosong)."
                        );
                    }
                    // Jika tidak wajib (seperti No. WA), nilai kosong aman dan dilewati
                    continue;
                }

                // 2. Cek Format & Validasi Berdasarkan validation_type
                $isValid = $this->evaluateRule($rule, $trimmedValue);

                if (! $isValid) {
                    $this->recordAnomaly(
                        $anomalies,
                        $anomaliesByRow,
                        $rowsWithAnomalies,
                        $rowIndex,
                        $rowNumber,
                        $rule,
                        $cellValue,
                        $rule->error_message ?: "Nilai pada kolom '{$rule->column_name}' tidak valid."
                    );
                }
            }
        }

        $totalRows = count($rows);
        $totalAnomalousRows = count($rowsWithAnomalies);
        $validRowsCount = max(0, $totalRows - $totalAnomalousRows);

        return [
            'total_rows' => $totalRows,
            'valid_rows_count' => $validRowsCount,
            'anomalies_count' => count($anomalies),
            'anomalous_rows_count' => $totalAnomalousRows,
            'anomalies' => $anomalies,
            'anomalies_by_row' => $anomaliesByRow,
        ];
    }

    /**
     * Mengambil nilai sel dari baris data.
     */
    private function extractCellValue(array $row, ValidationRule $rule): mixed
    {
        if (array_key_exists($rule->column_index, $row)) {
            return $row[$rule->column_index];
        }

        if (array_key_exists($rule->column_name, $row)) {
            return $row[$rule->column_name];
        }

        return null;
    }

    /**
     * Mengevaluasi keabsahan nilai sel terhadap aturan.
     */
    private function evaluateRule(ValidationRule $rule, mixed $value): bool
    {
        $strValue = (string) $value;

        switch ($rule->validation_type) {
            case 'regex':
                $pattern = $rule->parameters['pattern'] ?? null;
                if (! $pattern) {
                    return true;
                }
                return (bool) preg_match($pattern, $strValue);

            case 'in_list':
                $options = $rule->parameters['options'] ?? [];
                if (empty($options)) {
                    return true;
                }
                // Case-insensitive comparison
                foreach ($options as $opt) {
                    if (strcasecmp(trim($opt), trim($strValue)) === 0) {
                        return true;
                    }
                }
                return false;

            case 'date_format':
                return $this->isValidDate($value);

            case 'phone':
                // Bersihkan spasi, tanda hubung, atau tanda kurung
                $cleaned = preg_replace('/[\s\-\(\)]+/', '', $strValue);
                $pattern = $rule->parameters['pattern'] ?? '/^(\+?62|08)\d{8,13}$/';
                return (bool) preg_match($pattern, $cleaned);

            case 'numeric':
                if (! is_numeric($strValue)) {
                    return false;
                }
                $num = (float) $strValue;
                if (isset($rule->parameters['min']) && $num < $rule->parameters['min']) {
                    return false;
                }
                if (isset($rule->parameters['max']) && $num > $rule->parameters['max']) {
                    return false;
                }
                return true;

            case 'none':
            default:
                if (isset($rule->parameters['min_length']) && mb_strlen($strValue) < $rule->parameters['min_length']) {
                    return false;
                }
                return true;
        }
    }

    /**
     * Memeriksa apakah nilai merupakan tanggal yang valid (termasuk serial date Excel).
     */
    private function isValidDate(mixed $value): bool
    {
        if ($value instanceof DateTimeInterface) {
            return true;
        }

        $str = trim((string) $value);

        // Jika berupa serial integer Excel (misal: 44927)
        if (is_numeric($str) && (int) $str > 1000 && (int) $str < 100000) {
            return true;
        }

        // Coba parsing dengan berbagai format umum
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'Y-m-d H:i:s', 'd/m/Y H:i:s'];
        foreach ($formats as $fmt) {
            $parsed = Carbon::createFromFormat($fmt, $str);
            if ($parsed && $parsed->format($fmt) === $str) {
                return true;
            }
        }

        try {
            $carbon = Carbon::parse($str);
            return $carbon->isValid();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Mencatat anomali yang ditemukan.
     */
    private function recordAnomaly(
        array &$anomalies,
        array &$anomaliesByRow,
        array &$rowsWithAnomalies,
        int $rowIndex,
        int $rowNumber,
        ValidationRule $rule,
        mixed $value,
        string $message
    ): void {
        $anomaly = [
            'row_index' => $rowIndex,
            'row_number' => $rowNumber,
            'column_name' => $rule->column_name,
            'column_index' => $rule->column_index,
            'original_value' => $value,
            'error_message' => $message,
            'needs_confirmation' => $rule->needs_confirmation,
            'confirmation_note' => $rule->confirmation_note,
        ];

        $anomalies[] = $anomaly;
        $anomaliesByRow[$rowIndex][] = $anomaly;
        $rowsWithAnomalies[$rowIndex] = true;
    }
}
