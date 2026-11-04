<?php

namespace App\Services;

use DateTimeImmutable;

class EmployeeDataSummary
{
    private const FIELD_HEADERS = [
        'gender' => ['jeniskelamin', 'gender'],
        'rank' => ['golakhir', 'golonganakhir', 'golongan'],
        'education' => ['pendidikansk', 'pendidikan', 'tingkatpendidikan'],
        'birth_date' => ['tgllahir', 'tanggallahir'],
        'tenure' => ['mk', 'masakerja'],
        'retirement_reminder' => ['pengingatpensiun', 'tmtpensiun'],
    ];

    private const AGE_GROUPS = [
        ['label' => '< 30', 'minimum' => 0, 'maximum' => 29],
        ['label' => '30–39', 'minimum' => 30, 'maximum' => 39],
        ['label' => '40–49', 'minimum' => 40, 'maximum' => 49],
        ['label' => '50–59', 'minimum' => 50, 'maximum' => 59],
        ['label' => '60+', 'minimum' => 60, 'maximum' => null],
    ];

    public function __construct(private DateValueParser $dateParser = new DateValueParser) {}

    /**
     * Summarize the latest stored rows without changing or rereading the source workbook.
     *
     * @param  array<int, string>  $headers
     * @param  iterable<array<int|string, mixed>>  $rows
     * @param  array<int, string>  $genderOptions
     * @return array{
     *     total_rows: int,
     *     gender: array<string, mixed>,
     *     rank: array<string, mixed>,
     *     education: array<string, mixed>,
     *     age: array<string, mixed>,
     *     tenure: array<string, mixed>,
     *     retirement: array<string, mixed>
     * }
     */
    public function summarize(array $headers, iterable $rows, array $genderOptions = []): array
    {
        $columns = $this->resolveColumns($headers);
        $genderOptions = array_fill_keys(array_map($this->normalizeValue(...), $genderOptions), true);
        $summary = [
            'total_rows' => 0,
            'gender' => [
                'available' => $columns['gender'] !== null,
                'male' => 0,
                'female' => 0,
                'classified_count' => 0,
                'male_percentage' => null,
                'female_percentage' => null,
                'male_values' => [],
                'female_values' => [],
                'unrecognized_categories' => [],
                'missing_count' => 0,
            ],
            'rank' => $this->emptyDistribution($columns['rank'] !== null),
            'education' => $this->emptyDistribution($columns['education'] !== null),
            'age' => [
                'available' => $columns['birth_date'] !== null,
                'count' => 0,
                'average' => null,
                'groups' => [],
                'unreadable_count' => 0,
            ],
            'tenure' => [
                'available' => $columns['tenure'] !== null,
                'count' => 0,
                'average_years' => null,
                'unreadable_count' => 0,
            ],
            'retirement' => [
                'source_column_available' => $columns['retirement_reminder'] !== null,
                'calculated' => false,
                'message' => 'Tidak dihitung karena batas usia pensiun yang berlaku belum dikonfirmasi untuk profil ini.',
            ],
        ];

        $maleValues = [];
        $femaleValues = [];
        $unrecognizedGenders = [];
        $rankCategories = [];
        $educationCategories = [];
        $ageGroups = array_fill_keys(array_column(self::AGE_GROUPS, 'label'), 0);
        $ageTotal = 0;
        $tenureTotalMonths = 0;
        $today = DateTimeImmutable::createFromInterface(now()->startOfDay());

        foreach ($rows as $row) {
            $summary['total_rows']++;

            if ($columns['gender'] !== null) {
                $value = $this->cellValue($row, $columns['gender'], $headers);
                $label = $this->displayValue($value);

                if ($label === null) {
                    $summary['gender']['missing_count']++;
                } else {
                    $category = $this->genderCategory($label, $genderOptions);

                    if ($category === 'male') {
                        $summary['gender']['male']++;
                        $this->countCategory($maleValues, $label);
                    } elseif ($category === 'female') {
                        $summary['gender']['female']++;
                        $this->countCategory($femaleValues, $label);
                    } else {
                        $this->countCategory($unrecognizedGenders, $label);
                    }
                }
            }

            if ($columns['rank'] !== null) {
                $this->countDistributionValue(
                    $summary['rank'],
                    $rankCategories,
                    $this->cellValue($row, $columns['rank'], $headers),
                );
            }

            if ($columns['education'] !== null) {
                $this->countDistributionValue(
                    $summary['education'],
                    $educationCategories,
                    $this->cellValue($row, $columns['education'], $headers),
                );
            }

            if ($columns['birth_date'] !== null) {
                $birthDate = $this->dateParser->parse(
                    $this->cellValue($row, $columns['birth_date'], $headers)
                );

                if ($birthDate === null || $birthDate > $today) {
                    $summary['age']['unreadable_count']++;
                } else {
                    $age = $birthDate->diff($today)->y;
                    $summary['age']['count']++;
                    $ageTotal += $age;

                    foreach (self::AGE_GROUPS as $group) {
                        if ($age >= $group['minimum'] && ($group['maximum'] === null || $age <= $group['maximum'])) {
                            $ageGroups[$group['label']]++;

                            break;
                        }
                    }
                }
            }

            if ($columns['tenure'] !== null) {
                $months = $this->tenureInMonths(
                    $this->cellValue($row, $columns['tenure'], $headers)
                );

                if ($months === null) {
                    $summary['tenure']['unreadable_count']++;
                } else {
                    $summary['tenure']['count']++;
                    $tenureTotalMonths += $months;
                }
            }
        }

        $summary['gender']['classified_count'] = $summary['gender']['male'] + $summary['gender']['female'];
        if ($summary['gender']['classified_count'] > 0) {
            $summary['gender']['male_percentage'] = round(
                $summary['gender']['male'] / $summary['gender']['classified_count'] * 100,
                1,
            );
            $summary['gender']['female_percentage'] = round(
                $summary['gender']['female'] / $summary['gender']['classified_count'] * 100,
                1,
            );
        }
        $summary['gender']['male_values'] = $this->sortedCategories($maleValues);
        $summary['gender']['female_values'] = $this->sortedCategories($femaleValues);
        $summary['gender']['unrecognized_categories'] = $this->sortedCategories($unrecognizedGenders);

        $summary['rank']['categories'] = $this->sortedCategories($rankCategories);
        $summary['education']['categories'] = $this->sortedCategories($educationCategories);

        if ($summary['age']['count'] > 0) {
            $summary['age']['average'] = round($ageTotal / $summary['age']['count'], 1);
            $summary['age']['groups'] = $ageGroups;
        }

        if ($summary['tenure']['count'] > 0) {
            $summary['tenure']['average_years'] = round(
                $tenureTotalMonths / $summary['tenure']['count'] / 12,
                1,
            );
        }

        return $summary;
    }

    /**
     * @return array{available: bool, categories: array<int, array{label: string, count: int}>, missing_count: int}
     */
    private function emptyDistribution(bool $available): array
    {
        return [
            'available' => $available,
            'categories' => [],
            'missing_count' => 0,
        ];
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, ?int>
     */
    private function resolveColumns(array $headers): array
    {
        $normalizedHeaders = [];
        foreach ($headers as $index => $header) {
            $normalizedHeaders[$index] = $this->normalizeHeader((string) $header);
        }

        $columns = [];
        foreach (self::FIELD_HEADERS as $field => $aliases) {
            $columns[$field] = null;

            foreach ($normalizedHeaders as $index => $header) {
                if (in_array($header, $aliases, true)) {
                    $columns[$field] = (int) $index;

                    break;
                }
            }
        }

        return $columns;
    }

    /**
     * @param  array<int|string, mixed>  $row
     * @param  array<int, string>  $headers
     */
    private function cellValue(array $row, int $columnIndex, array $headers): mixed
    {
        if (array_key_exists($columnIndex, $row)) {
            return $row[$columnIndex];
        }

        $header = $headers[$columnIndex] ?? null;

        return $header !== null && array_key_exists($header, $row) ? $row[$header] : null;
    }

    /**
     * @param  array<string, array{label: string, count: int}>  $categories
     */
    private function countCategory(array &$categories, string $value): void
    {
        $key = mb_strtolower($value);

        if (! isset($categories[$key])) {
            $categories[$key] = [
                'label' => $value,
                'count' => 0,
            ];
        }

        $categories[$key]['count']++;
    }

    /**
     * @param  array<string, mixed>  $distribution
     * @param  array<string, array{label: string, count: int}>  $categories
     */
    private function countDistributionValue(array &$distribution, array &$categories, mixed $value): void
    {
        $label = $this->displayValue($value);
        if ($label === null) {
            $distribution['missing_count']++;

            return;
        }

        $this->countCategory($categories, $label);
    }

    /**
     * @param  array<string, array{label: string, count: int}>  $categories
     * @return array<int, array{label: string, count: int}>
     */
    private function sortedCategories(array $categories): array
    {
        uasort($categories, static fn (array $first, array $second): int => strnatcasecmp($first['label'], $second['label']));

        return array_values($categories);
    }

    private function genderCategory(string $value, array $genderOptions): ?string
    {
        $normalized = $this->normalizeHeader($value);

        if (! isset($genderOptions[$normalized])) {
            return null;
        }

        return match ($normalized) {
            'l', 'lk', 'lakilaki', 'pria', 'male', 'm' => 'male',
            'p', 'pr', 'perempuan', 'wanita', 'female', 'f' => 'female',
            default => null,
        };
    }

    private function displayValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeHeader(string $value): string
    {
        return mb_strtolower((string) preg_replace('/[^a-z0-9]/i', '', trim($value)));
    }

    private function normalizeValue(string $value): string
    {
        return $this->normalizeHeader($value);
    }

    private function tenureInMonths(mixed $value): ?int
    {
        $value = $this->displayValue($value);
        if ($value === null) {
            return null;
        }

        $matched = preg_match(
            '/^\s*(?:(\d+)\s*(?:tahun|thn|th)\.?)?\s*(?:(\d+)\s*(?:bulan|bln|bl)\.?)?\s*$/iu',
            $value,
            $matches,
        );

        if ($matched !== 1 || (($matches[1] ?? '') === '' && ($matches[2] ?? '') === '')) {
            return null;
        }

        return ((int) ($matches[1] ?? 0) * 12) + (int) ($matches[2] ?? 0);
    }
}
