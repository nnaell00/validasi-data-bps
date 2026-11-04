<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DateValueParser
{
    /**
     * Parse a date using the same formats accepted by validation rules.
     */
    public function parse(mixed $value, mixed $configuredFormats = []): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (! is_scalar($value)) {
            return null;
        }

        $dateValue = trim((string) $value);
        if ($dateValue === '') {
            return null;
        }

        if (is_numeric($dateValue)) {
            $serial = (float) $dateValue;

            if ($serial > 1000 && $serial <= 2958465) {
                try {
                    return DateTimeImmutable::createFromInterface(
                        ExcelDate::excelToDateTimeObject($serial)
                    );
                } catch (\Exception) {
                    return null;
                }
            }
        }

        $configuredFormats = is_string($configuredFormats)
            ? explode('|', $configuredFormats)
            : (is_array($configuredFormats) ? $configuredFormats : []);
        $formats = array_unique(array_merge($configuredFormats, [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
            'Y-m-d H:i:s',
            'd/m/Y H:i:s',
            'm/d/Y',
            'm-d-Y',
            'm/d/y',
            'n/j/Y',
            'n/j/y',
        ]));

        foreach ($formats as $format) {
            if (! is_string($format) || $format === '') {
                continue;
            }

            $parsed = DateTimeImmutable::createFromFormat('!'.$format, $dateValue);
            $errors = DateTimeImmutable::getLastErrors();

            if (
                $parsed !== false
                && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
                && $parsed->format($format) === $dateValue
            ) {
                return $parsed;
            }
        }

        return null;
    }
}
