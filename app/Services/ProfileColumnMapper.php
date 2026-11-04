<?php

namespace App\Services;

class ProfileColumnMapper
{
    /**
     * @var array<string, array<string, array<int, string>>>
     */
    private const PROFILE_FIELDS = [
        'data-kepegawaian' => [
            'nomor' => ['No.', 'Nomor'],
            'nama' => ['Nama'],
            'nip' => ['NIP'],
            'nik' => ['NIK'],
            'jenis_kelamin' => ['Jenis Kelamin', 'Gender'],
            'golongan' => ['Golongan', 'Gol.Akhir', 'Golongan Terakhir'],
            'pangkat' => ['Pangkat'],
            'pendidikan' => ['Pendidikan', 'Pendidikan (SK)', 'Pendidikan Terakhir'],
            'tanggal_lahir' => ['Tanggal Lahir', 'Tgl.Lahir'],
            'tanggal_lulus' => ['Tanggal Lulus', 'Tgl.Lulus (SK)'],
            'tmt_golongan' => ['TMT Golongan', 'TMT Gol.'],
            'masa_kerja' => ['Masa Kerja', 'MK'],
            'pengingat_pensiun' => ['Pengingat Pensiun'],
            'pengingat_sks' => ['Pengingat SKS'],
            'pengingat_kgb' => ['Pengingat KGB'],
            'status_jabatan' => ['Status Jabatan', 'Stat.Jabatan'],
            'no_wa' => ['No. WA', 'No WA', 'Nomor WhatsApp'],
        ],
    ];

    /**
     * @param  array<int, mixed>  $headers
     * @return array{
     *     columns: array<string, int|null>,
     *     ambiguous: array<string, array<int, int>>
     * }
     */
    public function resolve(string $profileSlug, array $headers): array
    {
        $fields = self::PROFILE_FIELDS[$profileSlug] ?? [];
        $columns = [];
        $ambiguous = [];

        foreach ($fields as $field => $aliases) {
            $matches = $this->matchingHeaderIndexes($headers, $aliases);
            $columns[$field] = count($matches) === 1 ? $matches[0] : null;

            if (count($matches) > 1) {
                $ambiguous[$field] = $matches;
            }
        }

        return [
            'columns' => $columns,
            'ambiguous' => $ambiguous,
        ];
    }

    /**
     * Resolve a validation rule's source column. Unknown profile fields only match
     * an exactly normalized rule name; no positional fallback is used.
     *
     * @param  array<int, mixed>  $headers
     */
    public function resolveRuleColumnIndex(string $profileSlug, string $ruleColumnName, array $headers): ?int
    {
        $fields = self::PROFILE_FIELDS[$profileSlug] ?? [];

        foreach ($fields as $aliases) {
            if (in_array($this->normalize($ruleColumnName), array_map($this->normalize(...), $aliases), true)) {
                $matches = $this->matchingHeaderIndexes($headers, $aliases);

                return count($matches) === 1 ? $matches[0] : null;
            }
        }

        $matches = $this->matchingHeaderIndexes($headers, [$ruleColumnName]);

        return count($matches) === 1 ? $matches[0] : null;
    }

    /**
     * Find the most likely header row among the first 25 worksheet rows.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function detectHeaderRow(string $profileSlug, array $rows): ?int
    {
        $fields = self::PROFILE_FIELDS[$profileSlug] ?? [];
        if ($fields === []) {
            return $rows === [] ? null : 0;
        }

        $bestIndex = null;
        $bestScore = 0;

        foreach (array_slice($rows, 0, 25, true) as $rowIndex => $row) {
            $resolution = $this->resolve($profileSlug, $row);
            $score = 0;

            foreach ($resolution['columns'] as $columnIndex) {
                if ($columnIndex !== null) {
                    $score++;
                }
            }

            foreach ($resolution['ambiguous'] as $indexes) {
                if ($indexes !== []) {
                    $score++;
                }
            }

            $identifyingFieldFound = $resolution['columns']['nama'] !== null
                || $resolution['columns']['nip'] !== null
                || isset($resolution['ambiguous']['nama'])
                || isset($resolution['ambiguous']['nip']);

            if ($score >= 2 && $identifyingFieldFound && $score > $bestScore) {
                $bestIndex = $rowIndex;
                $bestScore = $score;
            }
        }

        return $bestIndex;
    }

    /**
     * @param  array<int, mixed>  $headers
     * @param  array<int, string>  $aliases
     * @return array<int, int>
     */
    private function matchingHeaderIndexes(array $headers, array $aliases): array
    {
        $normalizedAliases = array_map($this->normalize(...), $aliases);
        $matches = [];

        foreach ($headers as $index => $header) {
            if (in_array($this->normalize((string) $header), $normalizedAliases, true)) {
                $matches[] = (int) $index;
            }
        }

        return $matches;
    }

    private function normalize(string $value): string
    {
        return mb_strtolower((string) preg_replace('/[^\p{L}\p{N}]+/u', '', trim($value)));
    }
}
