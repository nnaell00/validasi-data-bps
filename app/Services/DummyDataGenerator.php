<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DummyDataGenerator
{
    /**
     * Menghasilkan file Excel sintetis data kepegawaian BPS Purwakarta
     * berisi data valid dan data anomali untuk pengujian sistem.
     *
     * @return string Path file spreadsheet sementara
     */
    public function generate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Kepegawaian');

        // Header kolom sesuai rancangan struktur
        $headers = [
            'No.',
            'Nama',
            'NIP',
            'Stat.Jabatan',
            'Pangkat',
            'Gol.Akhir',
            'TMT Gol.',
            'Pendidikan (SK)',
            'Tgl.Lulus (SK)',
            'Jenis Kelamin',
            'Tgl.Lahir',
            'NIK',
            'Kolom_Raw_1', // Kolom belum diketahui 1 (dipertahankan)
            'Kolom_Raw_2', // Kolom belum diketahui 2 (dipertahankan)
            'Kolom_Raw_3', // Kolom belum diketahui 3 (dipertahankan)
            'MK',
            'Pengingat SKS',
            'Pengingat Pensiun',
            'Pengingat KGB',
            'No. WA',
        ];

        // Tulis header di baris ke-1
        foreach ($headers as $colIndex => $headerName) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue("{$colLetter}1", $headerName);
        }

        // Data dummy sintetis (20 baris: kombinasi data valid dan variasi anomali)
        $data = [
            // 1. Data Valid (No WA terisi)
            [1, 'Ahmad Fauzi, S.Stat.', '198801152011011002', 'Fungsional Statistisi Muda', 'Penata', 'III/c', '2021-04-01', 'S1 Statistika', '2010-09-15', 'L', '1988-01-15', '3214011501880001', 'RAW-X01', 'RAW-Y01', 'RAW-Z01', '10 Thn 3 Bln', '2025-09-01', '2048-02-01', '2025-04-01', '081234567891'],
            // 2. Data Valid (No WA kosong - BUKAN ANOMALI sesuai aturan khusus)
            [2, 'Siti Nurhaliza, S.Tr.Stat.', '199503202018022001', 'Statistisi Pertama', 'Penata Muda', 'III/a', '2022-10-01', 'D4 Statistika Polstat STIS', '2017-08-25', 'P', '1995-03-20', '3214026003950002', 'RAW-X02', 'RAW-Y02', 'RAW-Z02', '5 Thn 8 Bln', '2026-03-01', '2055-04-01', '2024-10-01', ''],
            // 3. Data Valid (Format NIP standar BKN dengan spasi)
            [3, 'Budi Santoso, M.Si.', '19820510 200604 1 003', 'Pranata Komputer Madya', 'Pembina', 'IV/a', '2020-04-01', 'S2 Ilmu Komputer', '2012-07-10', 'L', '1982-05-10', '3214031005820003', 'RAW-X03', 'RAW-Y03', 'RAW-Z03', '17 Thn 0 Bln', '2026-01-15', '2042-06-01', '2024-04-01', '085712345678'],
            // 4. Data Valid (No WA kosong)
            [4, 'Dewi Kartika, A.Md.', '199207042014032002', 'Pranata Komputer Mahir', 'Pengatur Tk. I', 'II/d', '2021-10-01', 'D3 Manajemen Informatika', '2013-09-01', 'P', '1992-07-04', '3214014407920004', 'RAW-X04', 'RAW-Y04', 'RAW-Z04', '9 Thn 5 Bln', '2025-11-20', '2052-08-01', '2025-10-01', null],
            // 5. Data Valid
            [5, 'Rendra Wijaya, S.E.', '199011122015031004', 'Analis Pengelolaan Keuangan APBN', 'Penata Muda Tk. I', 'III/b', '2021-04-01', 'S1 Ekonomi Pembangunan', '2013-11-20', 'L', '1990-11-12', '3214051211900005', 'RAW-X05', 'RAW-Y05', 'RAW-Z05', '8 Thn 2 Bln', '2026-06-01', '2050-12-01', '2025-04-01', '6281399887766'],
            // 6. Data Valid
            [6, 'Indah Permatasari, S.E.', '199308182019012003', 'Pengelola Pengadaan Barang/Jasa', 'Penata Muda', 'III/a', '2023-04-01', 'S1 Manajemen', '2016-02-14', 'Perempuan', '1993-08-18', '3214065808930006', 'RAW-X06', 'RAW-Y06', 'RAW-Z06', '4 Thn 9 Bln', '2025-08-10', '2053-09-01', '2025-04-01', '087811223344'],
            // 7. Data Valid
            [7, 'Agus Supriyatna, S.Si.', '198604252009121001', 'Kepala Subbagian Umum', 'Penata Tk. I', 'III/d', '2020-10-01', 'S1 Matematika', '2008-08-20', 'Laki-laki', '1986-04-25', '3214012504860007', 'RAW-X07', 'RAW-Y07', 'RAW-Z07', '14 Thn 1 Bln', '2026-04-01', '2046-05-01', '2024-10-01', '081322334455'],
            
            // 8. ANOMALI: NIP salah (hanya 10 digit angka)
            [8, 'Bambang Kusuma, S.Sos.', '1987010199', 'Fungsional Umum', 'Penata Muda', 'III/a', '2021-04-01', 'S1 Administrasi Publik', '2010-06-15', 'L', '1987-01-01', '3214020101870008', 'RAW-X08', 'RAW-Y08', 'RAW-Z08', '7 Thn', '2025-04-01', '2047-02-01', '2025-04-01', '081233445566'],
            // 9. ANOMALI: Golongan tidak valid ('V/A' bukan golongan ASN)
            [9, 'Citra Lestari, S.Tr.Stat.', '199602142019022002', 'Statistisi Pertama', 'Penata Muda', 'V/A', '2022-04-01', 'D4 Statistika Polstat STIS', '2018-09-01', 'P', '1996-02-14', '3214015402960009', 'RAW-X09', 'RAW-Y09', 'RAW-Z09', '4 Thn 2 Bln', '2025-07-01', '2056-03-01', '2024-04-01', ''],
            // 10. ANOMALI: Nama Kosong (Wajib terisi)
            [10, '', '199109092015021003', 'Statistisi Ahli Muda', 'Penata', 'III/c', '2022-10-01', 'S1 Sains Data', '2013-10-10', 'L', '1991-09-09', '3214010909910010', 'RAW-X10', 'RAW-Y10', 'RAW-Z10', '8 Thn', '2026-09-01', '2051-10-01', '2024-10-01', '089611223344'],
            // 11. ANOMALI: Jenis Kelamin tidak valid ('Waria' bukan enum L/P)
            [11, 'Eka Wahyuni, A.Md.', '199405052016022001', 'Pengadministrasi Umum', 'Pengatur', 'II/c', '2020-04-01', 'D3 Akuntansi', '2015-08-15', 'Bukan-L-P', '1994-05-05', '3214014505940011', 'RAW-X11', 'RAW-Y11', 'RAW-Z11', '6 Thn', '2025-02-01', '2054-06-01', '2024-04-01', '081255667788'],
            // 12. ANOMALI: NIK kurang digit (hanya 12 digit, seharusnya 16 digit)
            [12, 'Fajar Ramadhan, S.Kom.', '198912252014021002', 'Pranata Komputer Ahli Muda', 'Penata Tk. I', 'III/d', '2022-04-01', 'S1 Sistem Informasi', '2012-03-20', 'L', '1989-12-25', '321401251289', 'RAW-X12', 'RAW-Y12', 'RAW-Z12', '9 Thn', '2026-05-01', '2049-01-01', '2024-04-01', '081377889900'],
            // 13. ANOMALI: Tgl Lahir teks rusak ('bukan-tanggal')
            [13, 'Gita Gutawa, S.Stat.', '199708172020012004', 'Statistisi Pertama', 'Penata Muda', 'III/a', '2023-10-01', 'S1 Statistika Terapan', '2019-09-01', 'P', 'FormatTglSalah', '3214015708970013', 'RAW-X13', 'RAW-Y13', 'RAW-Z13', '3 Thn', '2025-04-01', '2057-09-01', '2025-10-01', ''],
            // 14. ANOMALI: No WA tidak sesuai format Indonesia ('01234' bukan nomor seluler valid)
            [14, 'Hendra Gunawan, S.E.', '198506152010011005', 'Bendahara Pengeluaran', 'Penata Tk. I', 'III/d', '2020-04-01', 'S1 Keuangan', '2008-07-25', 'L', '1985-06-15', '3214011506850014', 'RAW-X14', 'RAW-Y14', 'RAW-Z14', '13 Thn', '2026-02-01', '2045-07-01', '2024-04-01', '01234567'],
            // 15. ANOMALI GANDA: NIP salah digit & NIK salah digit & Golongan salah
            [15, 'Irfan Hakim, S.Sos.', '99999', 'Staf Pengolah Data', 'Penata Muda', 'X/z', '2021-04-01', 'S1 Komunikasi', '2011-05-10', 'L', '1988-10-10', '123', 'RAW-X15', 'RAW-Y15', 'RAW-Z15', '5 Thn', '2025-12-01', '2048-11-01', '2025-04-01', '081988776655'],
        ];

        $rowNum = 2;
        foreach ($data as $row) {
            foreach ($row as $colIndex => $value) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                // Set explicit string type for NIP, NIK, No WA agar angka 0 di depan tidak hilang di Excel
                if (in_array($colIndex, [2, 10, 11, 19])) {
                    $sheet->setCellValueExplicit("{$colLetter}{$rowNum}", (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue("{$colLetter}{$rowNum}", $value);
                }
            }
            $rowNum++;
        }

        // Styling Header Excel
        $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A1:{$highestCol}1";
        
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '004B87'], // Biru BPS
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Auto-width kolom
        foreach (range(1, count($headers)) as $col) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $tempPath = storage_path('app/dummy_data_kepegawaian_bps_purwakarta.xlsx');
        if (! file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }
}
