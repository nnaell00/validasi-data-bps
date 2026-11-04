<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BatchRow;
use App\Models\RowAnomaly;
use App\Models\User;
use App\Models\ValidationBatch;
use App\Models\ValidationProfile;
use App\Models\ValidationRule;
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

    public function test_petugas_dashboard_counts_saved_anomalies_when_the_row_flag_is_stale(): void
    {
        $petugas = $this->createUser('Petugas Uji', 'petugas');
        $profile = $this->createProfile();
        $batch = $this->createBatch($petugas, $profile, 'validated');
        $row = $this->createRow($batch);
        $this->createAnomaly($batch, $row);

        $response = $this->actingAs($petugas)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats === [
                'processed_batches' => 1,
                'valid_rows' => 0,
                'anomalous_rows' => 1,
                'follow_up_rows' => 1,
            ];
        });
    }

    public function test_admin_dashboard_uses_saved_metrics_and_links_to_the_selected_records(): void
    {
        $admin = $this->createUser('Admin Uji', 'admin');
        $petugas = $this->createUser('Petugas Uji', 'petugas');
        $profile = $this->createProfile();
        ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'NIP',
            'column_index' => 0,
            'data_type' => 'string',
            'is_required' => true,
            'validation_type' => 'regex',
            'parameters' => ['pattern' => '^\\d+$'],
            'error_message' => 'NIP tidak valid.',
            'is_active' => true,
        ]);
        ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'Nama',
            'column_index' => 1,
            'data_type' => 'string',
            'is_required' => true,
            'validation_type' => 'none',
            'error_message' => 'Nama wajib diisi.',
            'is_active' => true,
        ]);

        $anomalousBatch = $this->createBatch($petugas, $profile, 'validated');
        $anomalousBatch->update([
            'file_name' => 'hasil_koreksi.xlsx',
            'total_rows' => 3,
        ]);
        $this->createRow($anomalousBatch);
        $anomalousRow = $this->createRow($anomalousBatch, true);
        $this->createRow($anomalousBatch);
        $this->createAnomaly($anomalousBatch, $anomalousRow);
        $this->createAnomaly($anomalousBatch, $anomalousRow);
        $anomalousRow->update(['has_anomalies' => false]);

        $completedBatch = $this->createBatch($petugas, $profile, 'completed');
        $completedBatch->update([
            'file_name' => 'validasi_terbaru.xlsx',
            'total_rows' => 1,
        ]);
        $this->createRow($completedBatch);
        $uploadedBatch = $this->createBatch($petugas, $profile, 'uploaded');
        $uploadedBatch->update(['file_name' => 'menunggu_validasi.xlsx']);

        AuditLog::create([
            'validation_batch_id' => $anomalousBatch->id,
            'batch_row_id' => $anomalousRow->id,
            'user_id' => $petugas->id,
            'column_name' => 'NIP',
            'old_value' => '000',
            'new_value' => '111',
            'action' => 'correction',
            'notes' => 'Koreksi uji.',
        ]);
        AuditLog::create([
            'validation_batch_id' => $completedBatch->id,
            'user_id' => $petugas->id,
            'action' => 'revalidation',
            'notes' => 'Validasi ulang uji.',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats === [
                'total_users' => 2,
                'admin_users' => 1,
                'petugas_users' => 1,
                'total_batches' => 3,
                'total_profiles' => 1,
            ];
        });
        $response->assertViewHas('recentBatches', function ($batches) use ($anomalousBatch, $completedBatch): bool {
            return $batches->count() === 2
                && $batches->contains(fn (ValidationBatch $batch): bool => $batch->is($anomalousBatch)
                    && $batch->rows_count === 3
                    && $batch->anomalous_rows_count === 1)
                && $batches->contains(fn (ValidationBatch $batch): bool => $batch->is($completedBatch)
                    && $batch->rows_count === 1
                    && $batch->anomalous_rows_count === 0);
        });
        $response->assertSee('Dashboard Administrator');
        $response->assertSee('Administrator Sistem');
        $response->assertSee('Pantau aktivitas dan kelola konfigurasi sistem validasi data.');
        $response->assertSee('Total Pengguna');
        $response->assertSee('Total Sesi Validasi');
        $response->assertSee('Total Profil Validasi');
        $response->assertSee('Aktivitas Validasi Terbaru');
        $response->assertDontSee('Riwayat Aktivitas Pengguna');
        $response->assertDontSee('Kelola Profil');
        $response->assertSee('hasil_koreksi.xlsx');
        $response->assertSee('validasi_terbaru.xlsx');
        $response->assertDontSee('menunggu_validasi.xlsx');
        $response->assertSee('Petugas Uji');
        $response->assertSee('Lihat Detail');
        $response->assertSee('href="'.route('users.index').'" aria-label="Total Pengguna: 2.', false);
        $response->assertSee('href="'.route('batches.index').'" aria-label="Total Sesi Validasi: 3.', false);
        $response->assertSee('href="'.route('profiles.index').'" aria-label="Total Profil Validasi: 1.', false);
        $response->assertSee('cursor-pointer', false);
        $response->assertSee('hover:shadow-md', false);
        $response->assertSee('active:scale-[0.99]', false);
        $response->assertSee('focus-visible:ring-4', false);
        $response->assertSee(route('users.index'), false);
        $response->assertSee(route('batches.index'), false);
        $response->assertSee(route('profiles.index'), false);
        $response->assertSee(route('validation.results', $anomalousBatch), false);
        $response->assertSee(route('validation.results', $completedBatch), false);

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('batches.index'))->assertOk();
        $this->actingAs($admin)->get(route('profiles.index'))->assertOk();
        $this->actingAs($admin)->get(route('profiles.show', $profile))->assertOk();
        $this->actingAs($admin)
            ->get(route('validation.results', $anomalousBatch))
            ->assertViewHas('batch', fn (ValidationBatch $batch): bool => $batch->is($anomalousBatch))
            ->assertViewHas('stats', fn (array $stats): bool => $stats['total_rows'] === 3
                && $stats['valid_rows'] === 2
                && $stats['anomalous_rows'] === 1
                && $stats['total_anomalies'] === 2);
        $this->actingAs($admin)
            ->get(route('validation.results', ['batch' => $anomalousBatch, 'filter' => 'anomalies_only']))
            ->assertViewHas('rows', fn ($rows): bool => $rows->total() === 1
                && $rows->getCollection()->contains(fn (BatchRow $row): bool => $row->is($anomalousRow)));
        $this->actingAs($admin)
            ->get(route('validation.results', $completedBatch))
            ->assertViewHas('batch', fn (ValidationBatch $batch): bool => $batch->is($completedBatch));
        $this->actingAs($admin)->get(route('validation.audit', $anomalousBatch))->assertOk();
        $this->actingAs($admin)->get(route('validation.audit', $completedBatch))->assertOk();
    }

    public function test_admin_dashboard_limits_recent_items_and_uses_responsive_validation_layout(): void
    {
        $admin = $this->createUser('Admin Ringkas', 'admin');
        $petugas = $this->createUser('Petugas Ringkas', 'petugas');
        $profile = $this->createProfile();

        for ($index = 1; $index <= 3; $index++) {
            ValidationProfile::create([
                'name' => "Profil Tambahan {$index}",
                'slug' => "profil-tambahan-{$index}",
                'description' => 'Profil validasi tambahan.',
                'is_active' => true,
            ]);
        }

        for ($index = 1; $index <= 6; $index++) {
            $batch = $this->createBatch($petugas, $profile, 'validated');
            $batch->update(['file_name' => "sesi-{$index}.xlsx"]);
            $row = $this->createRow($batch);

            AuditLog::create([
                'validation_batch_id' => $batch->id,
                'batch_row_id' => $row->id,
                'user_id' => $petugas->id,
                'action' => 'validation',
                'notes' => "Validasi sesi {$index}.",
            ]);
        }

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('recentBatches', fn ($batches): bool => $batches->count() === 5);
        $response->assertSee('hidden xl:block', false);
        $response->assertSee('xl:hidden', false);
        $response->assertDontSee('min-w-[1050px]', false);
    }

    public function test_admin_dashboard_shows_clear_empty_states_for_missing_records(): void
    {
        $admin = $this->createUser('Admin Kosong', 'admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Belum ada sesi validasi yang dijalankan');
        $response->assertDontSee('Riwayat Aktivitas Pengguna');
        $response->assertDontSee('Kelola Profil');
        $response->assertSee('Total Sesi Validasi');
        $response->assertSee('Total Profil Validasi');
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
