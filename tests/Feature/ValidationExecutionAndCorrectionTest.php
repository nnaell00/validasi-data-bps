<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BatchRow;
use App\Models\User;
use App\Models\ValidationBatch;
use App\Models\ValidationProfile;
use App\Models\ValidationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ValidationExecutionAndCorrectionTest extends TestCase
{
    use RefreshDatabase;

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
                0 => 2,
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

        // 4. Petugas Menjalankan Validasi Ulang (Re-validate)
        $revalResponse = $this->actingAs($petugas)->post("/batches/{$batch->id}/revalidate");
        $revalResponse->assertRedirect("/batches/{$batch->id}/results");

        $batch->refresh();
        $this->assertEquals('completed', $batch->status, 'Setelah dikoreksi, status batch harus completed / bersih.');
        $this->assertEquals(0, $batch->summary['total_anomalies']);
        $this->assertEquals(2, $batch->summary['valid_rows']);
    }
}
