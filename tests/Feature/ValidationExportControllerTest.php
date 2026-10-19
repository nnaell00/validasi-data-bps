<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BatchRow;
use App\Models\User;
use App\Models\ValidationBatch;
use App\Models\ValidationProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ValidationExportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_corrected_data_and_two_sheet_report_with_source_columns_preserved(): void
    {
        $this->travelTo(Carbon::parse('2026-10-19 06:31:54', 'UTC'));
        $petugas = $this->createUser('petugas', 'petugas@bps.go.id');
        $batch = $this->createBatch($petugas);
        $headers = $this->headers();
        $originalData = array_fill(0, 20, '');
        $originalData[0] = '1';
        $originalData[1] = 'Pegawai Sintetis';
        $originalData[2] = '000123456789012345';
        $originalData[11] = '0000000000000456';
        $originalData[12] = 'SUMBER-A';
        $originalData[13] = 'SUMBER-B';
        $originalData[14] = 'SUMBER-C';
        $currentData = $originalData;
        $currentData[1] = 'Nama Hasil Perbaikan';
        $currentData[2] = '000987654321098765';
        $currentData[11] = '0000000000000999';
        $currentData[12] = 'TIDAK-BOLEH-DIEKSPOR';
        $currentData[13] = 'TIDAK-BOLEH-DIEKSPOR';
        $currentData[14] = 'TIDAK-BOLEH-DIEKSPOR';

        $row = BatchRow::create([
            'validation_batch_id' => $batch->id,
            'row_index' => 0,
            'row_number' => 1,
            'original_data' => $originalData,
            'current_data' => $currentData,
            'is_corrected' => true,
        ]);

        $auditLog = AuditLog::create([
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $row->id,
            'user_id' => $petugas->id,
            'column_name' => 'NIP',
            'old_value' => '000123456789012345',
            'new_value' => '000987654321098765',
            'action' => 'correction',
            'notes' => 'Koreksi sintetis untuk pengujian.',
        ]);

        $reportResponse = $this->actingAs($petugas)
            ->get(route('validation.export.report', $batch));

        $reportResponse->assertStreamed();
        $reportResponse->assertDownload("laporan_validasi_sesi_{$batch->id}.xlsx");
        $report = $this->spreadsheetFromDownload($reportResponse);

        $this->assertSame([
            'Data Pegawai Hasil Perbaikan',
            'Rekapitulasi Audit Trail',
        ], $report->getSheetNames());
        $employeeSheet = $report->getSheet(0);
        foreach ($headers as $columnIndex => $header) {
            $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).'1';
            $this->assertSame($header, $employeeSheet->getCell($coordinate)->getValue());
        }
        $this->assertSame('Nama Hasil Perbaikan', $employeeSheet->getCell('B2')->getValue());
        $this->assertSame('000987654321098765', $employeeSheet->getCell('C2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $employeeSheet->getCell('C2')->getDataType());
        $this->assertSame('0000000000000999', $employeeSheet->getCell('L2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $employeeSheet->getCell('L2')->getDataType());
        $this->assertSame($headers[12], $employeeSheet->getCell('M1')->getValue());
        $this->assertSame('SUMBER-A', $employeeSheet->getCell('M2')->getValue());
        $this->assertSame('SUMBER-B', $employeeSheet->getCell('N2')->getValue());
        $this->assertSame('SUMBER-C', $employeeSheet->getCell('O2')->getValue());
        $this->assertContains($employeeSheet->getCell('T2')->getValue(), [null, '']);

        $auditSheet = $report->getSheet(1);
        $this->assertSame('Petugas', $auditSheet->getCell('B1')->getValue());
        $this->assertSame('Petugas Uji', $auditSheet->getCell('B2')->getValue());
        $this->assertSame('1', $auditSheet->getCell('C2')->getValue());
        $this->assertSame('NIP', $auditSheet->getCell('D2')->getValue());
        $this->assertSame('19/10/2026 13:31 WIB', $auditSheet->getCell('A2')->getValue());
        $this->assertSame('000123456789012345', $auditSheet->getCell('E2')->getValue());
        $this->assertSame('000987654321098765', $auditSheet->getCell('F2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $auditSheet->getCell('E2')->getDataType());
        $report->disconnectWorksheets();

        $dataResponse = $this->get(route('validation.export.data', $batch));

        $dataResponse->assertStreamed();
        $dataResponse->assertDownload("data_pegawai_hasil_perbaikan_sesi_{$batch->id}.xlsx");
        $dataSpreadsheet = $this->spreadsheetFromDownload($dataResponse);
        $this->assertSame(['Data Pegawai Hasil Perbaikan'], $dataSpreadsheet->getSheetNames());
        $this->assertSame('SUMBER-C', $dataSpreadsheet->getActiveSheet()->getCell('O2')->getValue());
        $dataSpreadsheet->disconnectWorksheets();
    }

    public function test_petugas_cannot_access_another_users_session_through_session_urls(): void
    {
        $owner = $this->createUser('petugas', 'pemilik@bps.go.id');
        $otherPetugas = $this->createUser('petugas', 'petugas-lain@bps.go.id');
        $batch = $this->createBatch($owner);
        $row = BatchRow::create([
            'validation_batch_id' => $batch->id,
            'row_index' => 0,
            'row_number' => 1,
            'original_data' => ['data sintetis'],
            'current_data' => ['data sintetis'],
        ]);

        $this->actingAs($otherPetugas)
            ->get(route('batches.preview', $batch))
            ->assertForbidden();
        $this->get(route('validation.results', $batch))->assertForbidden();
        $this->get(route('validation.audit', $batch))->assertForbidden();
        $this->get(route('validation.export.data', $batch))->assertForbidden();
        $this->get(route('validation.export.report', $batch))->assertForbidden();
        $this->get(route('validation.edit-row', [$batch, $row]))->assertForbidden();
        $this->put(route('validation.update-row', [$batch, $row]), ['data' => ['data sintetis']])->assertForbidden();
        $this->post(route('validation.execute', $batch))->assertForbidden();
        $this->post(route('validation.revalidate', $batch))->assertForbidden();
    }

    public function test_only_admin_and_petugas_roles_can_access_export_routes(): void
    {
        $petugas = $this->createUser('petugas', 'petugas@bps.go.id');
        $batch = $this->createBatch($petugas);
        $unsupportedUser = $this->createUser('auditor', 'auditor@bps.go.id');

        $this->actingAs($unsupportedUser)
            ->get(route('validation.export.data', $batch))
            ->assertForbidden();

        $admin = $this->createUser('admin', 'admin@bps.go.id');
        $this->actingAs($admin)
            ->get(route('validation.export.report', $batch))
            ->assertDownload("laporan_validasi_sesi_{$batch->id}.xlsx");

        auth()->logout();
        $this->get(route('validation.export.data', $batch))
            ->assertRedirect(route('login'));
    }

    private function createUser(string $role, string $email): User
    {
        return User::create([
            'name' => $role === 'petugas' ? 'Petugas Uji' : ucfirst($role).' Uji',
            'email' => $email,
            'role' => $role,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    private function createBatch(User $owner): ValidationBatch
    {
        $profile = ValidationProfile::create([
            'name' => 'Profil Uji',
            'slug' => 'profil-uji-'.$owner->id,
            'is_active' => true,
        ]);

        return ValidationBatch::create([
            'user_id' => $owner->id,
            'validation_profile_id' => $profile->id,
            'file_name' => 'data_sintetis.xlsx',
            'file_path' => 'synthetic/test.xlsx',
            'total_rows' => 1,
            'status' => 'completed',
            'headers' => $this->headers(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function headers(): array
    {
        $headers = array_map(fn (int $index): string => "Kolom {$index}", range(0, 19));
        $headers[0] = 'No.';
        $headers[1] = 'Nama';
        $headers[2] = 'NIP';
        $headers[11] = 'NIK';
        $headers[12] = 'Sumber Tidak Diketahui A';
        $headers[13] = 'Sumber Tidak Diketahui B';
        $headers[14] = 'Sumber Tidak Diketahui C';
        $headers[19] = 'No. WA';

        return $headers;
    }

    private function spreadsheetFromDownload(TestResponse $response): Spreadsheet
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'bps-xlsx-');
        $this->assertNotFalse($temporaryPath);

        file_put_contents($temporaryPath, $response->streamedContent());

        try {
            return IOFactory::load($temporaryPath);
        } finally {
            unlink($temporaryPath);
        }
    }
}
