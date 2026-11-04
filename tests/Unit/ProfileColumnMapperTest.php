<?php

namespace Tests\Unit;

use App\Services\ProfileColumnMapper;
use Tests\TestCase;

class ProfileColumnMapperTest extends TestCase
{
    public function test_it_maps_case_spacing_and_punctuation_variations_for_the_selected_profile(): void
    {
        $mapping = (new ProfileColumnMapper)->resolve('data-kepegawaian', [
            '  NAMA ',
            'Golongan',
            'Tanggal   Lahir',
            'Masa-Kerja',
            'Jenis Kelamin',
            'Pendidikan (SK)',
        ]);

        $this->assertSame(0, $mapping['columns']['nama']);
        $this->assertSame(1, $mapping['columns']['golongan']);
        $this->assertSame(2, $mapping['columns']['tanggal_lahir']);
        $this->assertSame(3, $mapping['columns']['masa_kerja']);
        $this->assertSame(4, $mapping['columns']['jenis_kelamin']);
        $this->assertSame(5, $mapping['columns']['pendidikan']);
        $this->assertSame([], $mapping['ambiguous']);
    }

    public function test_it_marks_duplicate_aliases_ambiguous_without_selecting_one(): void
    {
        $mapping = (new ProfileColumnMapper)->resolve('data-kepegawaian', [
            'Nama',
            'Pendidikan',
            'Pendidikan (SK)',
        ]);

        $this->assertNull($mapping['columns']['pendidikan']);
        $this->assertSame([1, 2], $mapping['ambiguous']['pendidikan']);
    }

    public function test_it_only_applies_aliases_to_the_matching_profile(): void
    {
        $mapping = (new ProfileColumnMapper)->resolve('profile-lain', ['Pendidikan']);

        $this->assertSame([], $mapping['columns']);
        $this->assertSame([], $mapping['ambiguous']);
    }
}
