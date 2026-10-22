<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BatchRow;
use App\Models\User;
use App\Models\ValidationBatch;
use App\Models\ValidationProfile;
use App\Models\ValidationRule;
use App\Services\ExcelDataParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ValidationExecutionAndCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_correction_rolls_back_when_audit_trail_cannot_be_written(): void
    {
        $petugas = User::create([
            'name' => 'Petugas Uji',
            'email' => 'petugas-transaction@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $profile = ValidationProfile::create([
            'name' => 'Profil Uji',
            'slug' => 'profil-transaksi',
            'is_active' => true,
        ]);
        $batch = ValidationBatch::create([
            'user_id' => $petugas->id,
            'validation_profile_id' => $profile->id,
            'file_name' => 'data_sintetis.xlsx',
            'file_path' => 'synthetic/test.xlsx',
            'total_rows' => 1,
            'status' => 'validated',
            'headers' => ['No.', 'Nama', 'NIP'],
        ]);
        $row = BatchRow::create([
            'validation_batch_id' => $batch->id,
            'row_index' => 0,
            'row_number' => 1,
            'original_data' => ['1', 'Pegawai Sintetis', '12345'],
            'current_data' => ['1', 'Pegawai Sintetis', '12345'],
        ]);
        $auditAttempts = 0;
        AuditLog::creating(static function () use (&$auditAttempts): void {
            $auditAttempts++;

            if ($auditAttempts === 2) {
                throw new \RuntimeException('Simulasi kegagalan audit trail.');
            }
        });

        $response = $this->actingAs($petugas)->put(route('validation.update-row', [$batch, $row]), [
            'data' => ['1', 'Nama Pegawai Diperbaiki', '000123456789012345'],
        ]);

        $response->assertStatus(500);
        $this->assertSame(['1', 'Pegawai Sintetis', '12345'], $row->refresh()->current_data);
        $this->assertFalse($row->is_corrected);
        $this->assertDatabaseMissing('audit_logs', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $row->id,
            'action' => 'correction',
        ]);
    }

    public function test_correction_rejects_changes_to_the_three_preserved_source_columns(): void
    {
        $petugas = User::create([
            'name' => 'Petugas Uji',
            'email' => 'petugas-raw@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $profile = ValidationProfile::create([
            'name' => 'Profil Uji',
            'slug' => 'profil-kolom-sumber',
            'is_active' => true,
        ]);
        $headers = array_map(fn (int $index): string => "Kolom {$index}", range(0, 19));
        $sourceData = array_map(fn (int $index): string => "nilai-{$index}", range(0, 19));
        $sourceData[2] = '000123456789012345';
        $sourceData[11] = '0000000000000001';
        $batch = ValidationBatch::create([
            'user_id' => $petugas->id,
            'validation_profile_id' => $profile->id,
            'file_name' => 'data_sintetis.xlsx',
            'file_path' => 'synthetic/test.xlsx',
            'total_rows' => 1,
            'status' => 'validated',
            'headers' => $headers,
        ]);
        $row = BatchRow::create([
            'validation_batch_id' => $batch->id,
            'row_index' => 0,
            'row_number' => 1,
            'original_data' => $sourceData,
            'current_data' => $sourceData,
        ]);
        $editResponse = $this->actingAs($petugas)->get(route('validation.edit-row', [$batch, $row]));

        $editResponse->assertOk();
        $editResponse->assertSee('readonly', false);

        $submittedData = $sourceData;
        $submittedData[2] = '000987654321098765';
        $submittedData[12] = 'nilai-diubah';
        $response = $this->put(route('validation.update-row', [$batch, $row]), ['data' => $submittedData]);

        $response->assertSessionHasErrors('data.12');
        $this->assertSame($sourceData, $row->refresh()->current_data);
        $this->assertDatabaseMissing('audit_logs', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $row->id,
            'action' => 'correction',
        ]);
    }

    public function test_validation_handles_excel_formatted_and_invalid_dates_without_failing(): void
    {
        $petugas = User::create([
            'name' => 'Petugas Uji',
            'email' => 'petugas-tanggal@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $profile = ValidationProfile::create([
            'name' => 'Profil Tanggal',
            'slug' => 'profil-tanggal',
            'is_active' => true,
        ]);
        ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'Tanggal Uji',
            'column_index' => 0,
            'data_type' => 'date',
            'is_required' => true,
            'validation_type' => 'date_format',
            'parameters' => ['format' => 'Y-m-d|d/m/Y|Y/m/d'],
            'error_message' => 'Tanggal harus berupa tanggal yang valid.',
            'is_active' => true,
        ]);

        $filePath = tempnam(sys_get_temp_dir(), 'bps-synthetic-dates-');
        if ($filePath === false) {
            self::fail('File sementara untuk pengujian tanggal tidak dapat dibuat.');
        }

        try {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray([
                ['Tanggal Uji', 'Catatan'],
                [45352, 'Tanggal Excel sintetis'],
                ['31/02/2024', 'Tanggal kalender tidak valid'],
                [null, 'Tanggal kosong'],
            ], null, 'A1');
            $sheet->getStyle('A2')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            (new Xlsx($spreadsheet))->save($filePath);

            $parsed = (new ExcelDataParser)->parse($filePath);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $this->assertSame('01/03/2024', $parsed['rows'][0][0]);

        $batch = ValidationBatch::create([
            'user_id' => $petugas->id,
            'validation_profile_id' => $profile->id,
            'file_name' => 'tanggal_sintetis.xlsx',
            'file_path' => 'synthetic/tanggal.xlsx',
            'total_rows' => count($parsed['rows']),
            'status' => 'uploaded',
            'headers' => $parsed['headers'],
        ]);

        foreach ($parsed['rows'] as $rowIndex => $rowData) {
            BatchRow::create([
                'validation_batch_id' => $batch->id,
                'row_index' => $rowIndex,
                'row_number' => $rowIndex + 1,
                'original_data' => $rowData,
                'current_data' => $rowData,
            ]);
        }

        $response = $this->actingAs($petugas)->post(route('validation.execute', $batch));

        $response->assertRedirect(route('validation.results', $batch));
        $this->assertSame('validated', $batch->refresh()->status);
        $this->assertDatabaseCount('row_anomalies', 2);
        $this->assertDatabaseHas('row_anomalies', [
            'validation_batch_id' => $batch->id,
            'column_name' => 'Tanggal Uji',
            'invalid_value' => '31/02/2024',
            'error_message' => 'Tanggal harus berupa tanggal yang valid.',
        ]);
        $this->assertSame($parsed['rows'], $batch->rows()->orderBy('row_index')->pluck('original_data')->all());
    }

    public function test_execution_anomaly_detection_correction_and_revalidation(): void
    {
        $petugas = User::create([
            'name' => 'Petugas Validasi BPS',
            'email' => 'petugas@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $profile = ValidationProfile::create([
            'name' => 'Data Kepegawaian',
            'slug' => 'data-kepegawaian',
            'is_active' => true,
        ]);

        // Aturan NIP: Wajib 18 digit
        ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'NIP',
            'column_index' => 2,
            'data_type' => 'string',
            'is_required' => true,
            'validation_type' => 'regex',
            'parameters' => ['pattern' => '/^\d{18}$/'],
            'error_message' => 'NIP harus terdiri dari 18 digit angka.',
            'needs_confirmation' => true,
            'is_active' => true,
        ]);
        $this->travelTo(Carbon::parse('2026-10-19 06:32:20', 'UTC'));

        // Buat batch dengan 2 baris (1 valid, 1 anomali NIP)
        $batch = ValidationBatch::create([
            'user_id' => $petugas->id,
            'validation_profile_id' => $profile->id,
            'file_name' => 'sample_data.xlsx',
            'file_path' => 'dummy/path.xlsx',
            'total_rows' => 2,
            'status' => 'uploaded',
            'headers' => ['No.', 'Nama', 'NIP'],
        ]);

        $row1 = BatchRow::create([
            'validation_batch_id' => $batch->id,
            'row_index' => 0,
            'row_number' => 1,
            'original_data' => [0 => 1, 1 => 'Budi Santoso', 2 => '198501012010011001'],
            'current_data' => [0 => 1, 1 => 'Budi Santoso', 2 => '198501012010011001'],
            'has_anomalies' => false,
        ]);

        $row2 = BatchRow::create([
            'validation_batch_id' => $batch->id,
            'row_index' => 1,
            'row_number' => 2,
            'original_data' => [0 => 2, 1 => 'Siti Aminah', 2 => '12345'], // Anomali: NIP hanya 5 digit
            'current_data' => [0 => 2, 1 => 'Siti Aminah', 2 => '12345'],
            'has_anomalies' => false,
        ]);

        // 1. Eksekusi Validasi
        $validateResponse = $this->actingAs($petugas)->post("/batches/{$batch->id}/validate");
        $validateResponse->assertRedirect("/batches/{$batch->id}/results");

        $batch->refresh();
        $this->assertEquals('validated', $batch->status);
        $this->assertEquals(1, $batch->summary['total_anomalies']);
        $this->assertEquals(1, $batch->summary['valid_rows']);

        $row2->refresh();
        $this->assertTrue($row2->has_anomalies);
        $this->assertDatabaseHas('row_anomalies', [
            'batch_row_id' => $row2->id,
            'column_name' => 'NIP',
            'invalid_value' => '12345',
        ]);

        // 2. Akses Halaman Hasil Validasi
        $resultsPage = $this->actingAs($petugas)->get("/batches/{$batch->id}/results");
        $resultsPage->assertStatus(200);
        $resultsPage->assertSee('Hasil Validasi: sample_data.xlsx');
        $resultsPage->assertSee('1 Anomali');

        // 3. Petugas Melakukan Koreksi pada baris ke-2
        $correctResponse = $this->actingAs($petugas)->put("/batches/{$batch->id}/rows/{$row2->id}", [
            'data' => [
                0 => '2',
                1 => 'Siti Aminah',
                2 => '199203152015022002', // NIP 18 digit yang benar
            ],
        ]);

        $correctResponse->assertRedirect("/batches/{$batch->id}/results");

        $row2->refresh();
        $this->assertTrue($row2->is_corrected);
        $this->assertEquals('199203152015022002', $row2->current_data[2]);
        $this->assertEquals('12345', $row2->original_data[2], 'Data asli sumber wajib tetap tidak berubah.');

        // Pastikan tercatat ke audit log
        $this->assertDatabaseHas('audit_logs', [
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $row2->id,
            'column_name' => 'NIP',
            'old_value' => '12345',
            'new_value' => '199203152015022002',
            'action' => 'correction',
        ]);

        $auditResponse = $this->actingAs($petugas)->get(route('validation.audit', $batch));

        $auditResponse->assertOk();
        $auditResponse->assertSee('19/10/2026 13:32 WIB');

        // 4. Petugas Menjalankan Validasi Ulang (Re-validate)
        $revalResponse = $this->actingAs($petugas)->post("/batches/{$batch->id}/revalidate");
        $revalResponse->assertRedirect("/batches/{$batch->id}/results");

        $batch->refresh();
        $this->assertEquals('completed', $batch->status, 'Setelah dikoreksi, status batch harus completed / bersih.');
        $this->assertEquals(0, $batch->summary['total_anomalies']);
        $this->assertEquals(2, $batch->summary['valid_rows']);
    }

    public function test_combined_nips_are_normalized_for_validation_and_revalidation_without_changing_source_data(): void
    {
        $petugas = User::create([
            'name' => 'Petugas NIP Gabungan',
            'email' => 'petugas-nip-gabungan@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $profile = ValidationProfile::create([
            'name' => 'Data Kepegawaian',
            'slug' => 'data-kepegawaian-kandidat',
            'is_active' => true,
        ]);
        ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'NIP',
            'column_index' => 2,
            'data_type' => 'string',
            'is_required' => true,
            'validation_type' => 'regex',
            'parameters' => ['pattern' => '/^[0-9]{18}$/D'],
            'error_message' => 'NIP harus terdiri tepat dari 18 digit angka.',
            'needs_confirmation' => true,
            'confirmation_note' => 'NIP tidak sesuai format yang didukung. Verifikasi nilai sumber sebelum menentukan perbaikan.',
            'is_active' => true,
        ]);
        $batch = ValidationBatch::create([
            'user_id' => $petugas->id,
            'validation_profile_id' => $profile->id,
            'file_name' => 'data_nip_gabungan_sintetis.xlsx',
            'file_path' => 'synthetic/data_nip_gabungan.xlsx',
            'total_rows' => 2,
            'status' => 'uploaded',
            'headers' => ['No.', 'Nama', 'NIP'],
        ]);
        $sourceNips = [
            '340015525 197601101997122001',
            '340015525197601101997122001',
        ];
        $rows = [];

        foreach ($sourceNips as $rowIndex => $sourceNip) {
            $rows[] = BatchRow::create([
                'validation_batch_id' => $batch->id,
                'row_index' => $rowIndex,
                'row_number' => $rowIndex + 1,
                'original_data' => [(string) ($rowIndex + 1), 'Pegawai Sintetis', $sourceNip],
                'current_data' => [(string) ($rowIndex + 1), 'Pegawai Sintetis', $sourceNip],
            ]);
        }

        $this->actingAs($petugas)
            ->post(route('validation.execute', $batch))
            ->assertRedirect(route('validation.results', $batch));

        $this->assertSame('completed', $batch->refresh()->status);
        $this->assertSame(0, $batch->summary['total_anomalies']);
        $this->assertDatabaseCount('row_anomalies', 0);

        foreach ($rows as $rowIndex => $row) {
            $row->refresh();
            $this->assertSame($sourceNips[$rowIndex], $row->original_data[2]);
            $this->assertSame('197601101997122001', $row->current_data[2]);
        }

        $this->post(route('validation.revalidate', $batch))
            ->assertRedirect(route('validation.results', $batch));

        $this->assertSame('completed', $batch->refresh()->status);
        $this->assertSame(0, $batch->summary['total_anomalies']);
        $this->assertDatabaseCount('row_anomalies', 0);

        foreach ($rows as $rowIndex => $row) {
            $row->refresh();
            $this->assertSame($sourceNips[$rowIndex], $row->original_data[2]);
            $this->assertSame('197601101997122001', $row->current_data[2]);
        }
    }
}
