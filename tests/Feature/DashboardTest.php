<?php

namespace Tests\Feature;

use App\Models\BatchRow;
use App\Models\RowAnomaly;
use App\Models\User;
use App\Models\ValidationBatch;
use App\Models\ValidationProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_dashboard_shows_own_latest_validation_metrics_and_relevant_menu(): void
    {
        $petugas = $this->createUser('Petugas Uji', 'petugas');
        $otherPetugas = $this->createUser('Petugas Lain', 'petugas');
        $profile = $this->createProfile();

        $validatedBatch = $this->createBatch($petugas, $profile, 'validated');
        $this->createRow($validatedBatch);
        $this->createRow($validatedBatch);

        $unresolvedRow = $this->createRow($validatedBatch, true);
        $this->createAnomaly($validatedBatch, $unresolvedRow);
        $this->createAnomaly($validatedBatch, $unresolvedRow);

        $correctedRow = $this->createRow($validatedBatch, true);
        $this->createAnomaly($validatedBatch, $correctedRow, true);

        $completedBatch = $this->createBatch($petugas, $profile, 'completed');
        $this->createRow($completedBatch);

        $uploadedBatch = $this->createBatch($petugas, $profile, 'uploaded');
        $this->createRow($uploadedBatch);

        $otherBatch = $this->createBatch($otherPetugas, $profile, 'validated');
        $otherRow = $this->createRow($otherBatch, true);
        $this->createAnomaly($otherBatch, $otherRow);

        $response = $this->actingAs($petugas)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats === [
                'processed_batches' => 2,
                'valid_rows' => 3,
                'anomalous_rows' => 2,
                'follow_up_rows' => 1,
            ];
        });
        $response->assertSee('Total Berkas Diproses');
        $response->assertSee('Baris Valid');
        $response->assertSee('Baris Anomali');
        $response->assertSee('Perlu Ditindaklanjuti');
        $response->assertSee(route('batches.create'), false);
        $response->assertSee('Riwayat Validasi');
        $response->assertSee('Profil Validasi Aktif');
        $response->assertSee('Data Kepegawaian');
        $response->assertSee('Aturan validasi mengikuti konfigurasi yang tersedia pada sistem.');
        $response->assertSee('Unggah berkas Excel dan periksa pratinjau');
        $response->assertSee('Jalankan validasi ulang dan periksa hasilnya');
        $response->assertSee('Ekspor hasil jika diperlukan');
        $response->assertDontSee('Total Akun');
        $response->assertDontSee('Pengguna');
        $response->assertDontSee('Profil & Aturan');
        $response->assertDontSee(route('users.index'), false);
        $response->assertDontSee(route('profiles.index'), false);
    }

    public function test_petugas_dashboard_shows_zero_for_missing_validation_data(): void
    {
        $petugas = $this->createUser('Petugas Kosong', 'petugas');

        $response = $this->actingAs($petugas)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats === [
                'processed_batches' => 0,
                'valid_rows' => 0,
                'anomalous_rows' => 0,
                'follow_up_rows' => 0,
            ];
        });
        $response->assertSee(route('batches.create'), false);
        $response->assertSee('Profil Data Kepegawaian belum aktif atau belum tersedia.');
    }

    public function test_admin_dashboard_keeps_account_statistics_and_admin_navigation(): void
    {
        $admin = $this->createUser('Admin Uji', 'admin');
        $this->createUser('Petugas Uji', 'petugas');
        $this->createProfile();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats === [
                'total_users' => 2,
                'total_batches' => 0,
                'total_records_checked' => 0,
                'total_anomalies' => 0,
                'total_corrected' => 0,
            ];
        });
        $response->assertSee('Total Akun');
        $response->assertSee('Akses & Menu');
        $response->assertSee(route('users.index'), false);
        $response->assertSee(route('profiles.index'), false);
    }

    private function createUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'email' => str($name)->slug().'@bps.go.id',
            'role' => $role,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    private function createProfile(): ValidationProfile
    {
        return ValidationProfile::create([
            'name' => 'Data Kepegawaian',
            'slug' => 'data-kepegawaian',
            'description' => 'Profil validasi data kepegawaian.',
            'is_active' => true,
        ]);
    }

    private function createBatch(User $user, ValidationProfile $profile, string $status): ValidationBatch
    {
        return ValidationBatch::create([
            'user_id' => $user->id,
            'validation_profile_id' => $profile->id,
            'file_name' => 'data_sintetis.xlsx',
            'file_path' => 'synthetic/data_sintetis.xlsx',
            'total_rows' => 0,
            'status' => $status,
            'headers' => [],
        ]);
    }

    private function createRow(ValidationBatch $batch, bool $hasAnomalies = false): BatchRow
    {
        return BatchRow::create([
            'validation_batch_id' => $batch->id,
            'row_index' => 0,
            'row_number' => 1,
            'original_data' => ['data'],
            'current_data' => ['data'],
            'has_anomalies' => $hasAnomalies,
            'is_corrected' => $hasAnomalies,
        ]);
    }

    private function createAnomaly(ValidationBatch $batch, BatchRow $row, bool $isResolved = false): RowAnomaly
    {
        return RowAnomaly::create([
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $row->id,
            'column_name' => 'Nama',
            'column_index' => 0,
            'invalid_value' => 'data',
            'error_message' => 'Nilai perlu diperiksa.',
            'is_resolved' => $isResolved,
        ]);
    }
}
