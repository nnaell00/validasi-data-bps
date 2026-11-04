<?php

namespace Tests\Unit;

use App\Services\EmployeeDataSummary;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmployeeDataSummaryTest extends TestCase
{
    public function test_it_summarizes_only_readable_values_from_current_rows(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));

        $summary = (new EmployeeDataSummary)->summarize(
            ['Tgl.Lahir', 'Pendidikan (SK)', 'Jenis Kelamin', 'Gol.Akhir', 'MK'],
            [
                [0 => '1986-04-25', 1 => 'S1 Statistika', 2 => ' l ', 3 => 'III/a', 4 => '10 Thn 3 Bln'],
                [0 => '1995-03-20', 1 => 'D3 Akuntansi', 2 => 'Perempuan', 3 => 'III/a', 4 => '5 Thn 8 Bln'],
                [0 => 'bukan-tanggal', 1 => 'S1 Statistika', 2 => 'Bukan-L-P', 3 => 'V/A', 4 => 'masa kerja tidak diketahui'],
                [0 => '', 1 => '', 2 => '', 3 => '', 4 => ''],
            ],
            ['L', 'LK', 'Laki-laki', 'P', 'PR', 'Perempuan', 'Wanita'],
        );

        $this->assertSame(4, $summary['total_rows']);
        $this->assertSame(1, $summary['gender']['male']);
        $this->assertSame(1, $summary['gender']['female']);
        $this->assertSame(2, $summary['gender']['classified_count']);
        $this->assertSame(50.0, $summary['gender']['male_percentage']);
        $this->assertSame(1, $summary['gender']['missing_count']);
        $this->assertSame([['label' => 'Bukan-L-P', 'count' => 1]], $summary['gender']['unrecognized_categories']);
        $this->assertSame([
            ['label' => 'III/a', 'count' => 2],
            ['label' => 'V/A', 'count' => 1],
        ], $summary['rank']['categories']);
        $this->assertSame(1, $summary['rank']['missing_count']);
        $this->assertSame([
            ['label' => 'D3 Akuntansi', 'count' => 1],
            ['label' => 'S1 Statistika', 'count' => 2],
        ], $summary['education']['categories']);
        $this->assertSame(2, $summary['age']['count']);
        $this->assertSame(34.5, $summary['age']['average']);
        $this->assertSame(2, $summary['age']['groups']['30–39']);
        $this->assertSame(2, $summary['age']['unreadable_count']);
        $this->assertSame(2, $summary['tenure']['count']);
        $this->assertSame(8.0, $summary['tenure']['average_years']);
        $this->assertSame(2, $summary['tenure']['unreadable_count']);
        $this->assertFalse($summary['retirement']['calculated']);
    }

    public function test_it_reports_unavailable_columns_without_fabricating_zero_statistics(): void
    {
        $summary = (new EmployeeDataSummary)->summarize(
            ['Nama'],
            [[0 => 'Pegawai Uji']],
            [],
        );

        $this->assertSame(1, $summary['total_rows']);
        $this->assertFalse($summary['gender']['available']);
        $this->assertFalse($summary['rank']['available']);
        $this->assertFalse($summary['education']['available']);
        $this->assertFalse($summary['age']['available']);
        $this->assertFalse($summary['tenure']['available']);
        $this->assertFalse($summary['retirement']['source_column_available']);
        $this->assertNull($summary['age']['average']);
        $this->assertNull($summary['tenure']['average_years']);
    }
}
