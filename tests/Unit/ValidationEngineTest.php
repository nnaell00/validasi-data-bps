<?php

namespace Tests\Unit;

use App\Models\ValidationProfile;
use App\Models\ValidationRule;
use App\Services\ValidationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_phone_number_is_not_an_anomaly(): void
    {
        $profile = ValidationProfile::create([
            'name' => 'Test Kepegawaian',
            'slug' => 'test-kepegawaian',
        ]);

        ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'No. WA',
            'column_index' => 19,
            'data_type' => 'string',
            'is_required' => false, // No WA bersifat opsional!
            'validation_type' => 'phone',
            'error_message' => 'Format nomor WhatsApp tidak valid.',
            'is_active' => true,
        ]);

        $rows = [
            [19 => ''], // Baris dengan No. WA kosong
            [19 => null], // Baris dengan No. WA null
            [19 => '081234567890'], // Baris dengan No. WA valid
        ];

        $engine = new ValidationEngine();
        $result = $engine->validate($profile, $rows);

        $this->assertEquals(0, $result['anomalies_count'], 'Nilai kosong pada No. WA tidak boleh dianggap sebagai anomali.');
        $this->assertEquals(3, $result['valid_rows_count']);
    }

    public function test_invalid_nip_and_required_name_detected_as_anomalies(): void
    {
        $profile = ValidationProfile::create([
            'name' => 'Test Profile',
            'slug' => 'test-profile',
        ]);

        ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'Nama',
            'column_index' => 1,
            'data_type' => 'string',
            'is_required' => true,
            'validation_type' => 'none',
            'error_message' => 'Nama pegawai wajib diisi.',
            'is_active' => true,
        ]);

        ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'NIP',
            'column_index' => 2,
            'data_type' => 'string',
            'is_required' => true,
            'validation_type' => 'regex',
            'parameters' => ['pattern' => '/^\d{18}$/'],
            'error_message' => 'NIP harus 18 digit angka.',
            'needs_confirmation' => true,
            'is_active' => true,
        ]);

        $rows = [
            // Baris 1: Nama kosong & NIP hanya 10 digit (2 anomali)
            [1 => '', 2 => '1234567890'],
            // Baris 2: Valid
            [1 => 'Ahmad Dahlan', 2 => '198501012010011001'],
        ];

        $engine = new ValidationEngine();
        $result = $engine->validate($profile, $rows);

        $this->assertEquals(2, $result['anomalies_count']);
        $this->assertEquals(1, $result['valid_rows_count']);
        $this->assertEquals(1, $result['anomalous_rows_count']);

        // Pastikan flag needs_confirmation tercatat
        $nipAnomaly = collect($result['anomalies'])->firstWhere('column_name', 'NIP');
        $this->assertNotNull($nipAnomaly);
        $this->assertTrue($nipAnomaly['needs_confirmation']);
    }
}
