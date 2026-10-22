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

    public function test_valid_dates_in_configured_formats_are_accepted(): void
    {
        $profile = $this->createDateValidationProfile();
        $engine = new ValidationEngine;

        $result = $engine->validate($profile, [
            [0 => '2024-02-29'],
            [0 => '29/02/2024'],
            [0 => '2024/02/29'],
        ]);

        $this->assertSame(0, $result['anomalies_count']);
        $this->assertSame(3, $result['valid_rows_count']);
    }

    public function test_empty_required_date_is_recorded_as_an_anomaly(): void
    {
        $profile = $this->createDateValidationProfile();
        $engine = new ValidationEngine;

        $result = $engine->validate($profile, [[0 => '']]);

        $this->assertSame(1, $result['anomalies_count']);
        $this->assertSame('', $result['anomalies'][0]['original_value']);
        $this->assertSame('Tanggal harus berupa tanggal yang valid.', $result['anomalies'][0]['error_message']);
    }

    public function test_invalid_dates_are_recorded_as_anomalies_without_throwing(): void
    {
        $profile = $this->createDateValidationProfile();
        $engine = new ValidationEngine;

        $result = $engine->validate($profile, [
            [0 => '31/02/2024'],
            [0 => 'bukan-tanggal'],
        ]);

        $this->assertSame(2, $result['anomalies_count']);
        $this->assertSame('31/02/2024', $result['anomalies'][0]['original_value']);
        $this->assertSame('bukan-tanggal', $result['anomalies'][1]['original_value']);
    }

    public function test_valid_excel_date_serial_is_accepted(): void
    {
        $profile = $this->createDateValidationProfile();
        $engine = new ValidationEngine;

        $result = $engine->validate($profile, [[0 => 45352]]);

        $this->assertSame(0, $result['anomalies_count']);
        $this->assertSame(1, $result['valid_rows_count']);
    }

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

        $engine = new ValidationEngine;
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

        $engine = new ValidationEngine;
        $result = $engine->validate($profile, $rows);

        $this->assertEquals(2, $result['anomalies_count']);
        $this->assertEquals(1, $result['valid_rows_count']);
        $this->assertEquals(1, $result['anomalous_rows_count']);

        // Pastikan flag needs_confirmation tercatat
        $nipAnomaly = collect($result['anomalies'])->firstWhere('column_name', 'NIP');
        $this->assertNotNull($nipAnomaly);
        $this->assertTrue($nipAnomaly['needs_confirmation']);
    }

    public function test_nip_accepts_exact_digits_and_whitespace_normalized_strings(): void
    {
        $profile = $this->createNipValidationProfile();
        $engine = new ValidationEngine;
        $values = [
            '000123456789012345',
            '00012345 6789012345',
            ' 000123456789012345 ',
        ];

        $result = $engine->validate($profile, array_map(
            static fn (string $value): array => [2 => $value],
            $values,
        ));

        $this->assertSame(0, $result['anomalies_count']);
        $this->assertSame(3, $result['valid_rows_count']);
    }

    public function test_combined_legacy_and_new_nip_is_normalized_to_new_nip(): void
    {
        $profile = $this->createNipValidationProfile();
        $engine = new ValidationEngine;
        $values = [
            '340015525 197601101997122001',
            '340015525197601101997122001',
        ];

        $result = $engine->validate($profile, array_map(
            static fn (string $value): array => [2 => $value],
            $values,
        ));

        $this->assertSame(0, $result['anomalies_count']);
        $this->assertSame([
            '197601101997122001',
            '197601101997122001',
        ], array_column($result['normalized_nips'], 2));
    }

    public function test_invalid_nip_without_confident_candidate_remains_an_anomaly(): void
    {
        $profile = $this->createNipValidationProfile();
        $engine = new ValidationEngine;
        $values = [
            '3400155251976011019971220010',
            '340015525 invalid-nip',
            '340015525 19760110199712200',
        ];

        $result = $engine->validate($profile, array_map(
            static fn (string $value): array => [2 => $value],
            $values,
        ));

        $this->assertSame(3, $result['anomalies_count']);
        $this->assertSame($values, array_column($result['anomalies'], 'original_value'));
        $this->assertStringContainsString('28', $result['anomalies'][0]['error_message']);
        $this->assertStringContainsString('digit angka', $result['anomalies'][1]['error_message']);
        $this->assertStringContainsString('27 digit', $result['anomalies'][2]['error_message']);
    }

    public function test_nip_whitespace_is_removed_for_validation_and_normalized_value_is_returned(): void
    {
        $profile = $this->createNipValidationProfile();
        $engine = new ValidationEngine;

        $result = $engine->validate($profile, [[2 => ' 19760110 1997122001 ']]);

        $this->assertSame(0, $result['anomalies_count']);
        $this->assertSame('197601101997122001', $result['normalized_nips'][0][2]);
    }

    public function test_recognized_gender_values_are_case_and_whitespace_insensitive(): void
    {
        $profile = $this->createGenderValidationProfile();
        $engine = new ValidationEngine;
        $values = [
            'L',
            'LK',
            'Laki-laki',
            ' l ',
            ' lk ',
            ' LAKI-LAKI ',
            'P',
            'PR',
            'Perempuan',
            ' p ',
            ' pr ',
            ' PEREMPUAN ',
            'Wanita',
            ' WANITA ',
        ];

        $result = $engine->validate($profile, array_map(
            static fn (string $value): array => [9 => $value],
            $values,
        ));

        $this->assertSame(0, $result['anomalies_count']);
        $this->assertSame(count($values), $result['valid_rows_count']);
    }

    public function test_unrecognized_gender_values_remain_anomalies_without_changing_originals(): void
    {
        $profile = $this->createGenderValidationProfile();
        $engine = new ValidationEngine;
        $values = ['Pria', ' Tidak diketahui '];

        $result = $engine->validate($profile, array_map(
            static fn (string $value): array => [9 => $value],
            $values,
        ));

        $this->assertSame(2, $result['anomalies_count']);
        $this->assertSame($values, array_column($result['anomalies'], 'original_value'));
    }

    private function createNipValidationProfile(): ValidationProfile
    {
        $profile = ValidationProfile::create([
            'name' => 'Profil NIP Uji',
            'slug' => 'profil-nip-uji',
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
            'is_active' => true,
        ]);

        return $profile;
    }

    private function createGenderValidationProfile(): ValidationProfile
    {
        $profile = ValidationProfile::create([
            'name' => 'Profil Gender Uji',
            'slug' => 'profil-gender-uji',
        ]);

        ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'Jenis Kelamin',
            'column_index' => 9,
            'data_type' => 'enum',
            'is_required' => true,
            'validation_type' => 'in_list',
            'parameters' => ['options' => ['L', 'LK', 'Laki-laki', 'P', 'PR', 'Perempuan', 'Wanita']],
            'error_message' => 'Jenis kelamin harus L, LK, Laki-laki, P, PR, Perempuan, atau Wanita.',
            'is_active' => true,
        ]);

        return $profile;
    }

    private function createDateValidationProfile(): ValidationProfile
    {
        $profile = ValidationProfile::create([
            'name' => 'Profil Tanggal Uji',
            'slug' => 'profil-tanggal-uji',
        ]);

        ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'Tanggal',
            'column_index' => 0,
            'data_type' => 'date',
            'is_required' => true,
            'validation_type' => 'date_format',
            'parameters' => ['format' => 'Y-m-d|d/m/Y|Y/m/d'],
            'error_message' => 'Tanggal harus berupa tanggal yang valid.',
            'is_active' => true,
        ]);

        return $profile;
    }
}
