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
use Tests\TestCase;

class AuditTrailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_admin_can_view_session_changes_and_petugas_cannot_view_other_sessions(): void
    {
        $owner = $this->createUser('petugas', 'pemilik@bps.go.id');
        $otherPetugas = $this->createUser('petugas', 'petugas-lain@bps.go.id');
        $admin = $this->createUser('admin', 'admin@bps.go.id');
        $batch = $this->createBatch($owner);
        $row = BatchRow::create([
            'validation_batch_id' => $batch->id,
            'row_index' => 0,
            'row_number' => 1,
            'original_data' => ['000000000000000001'],
            'current_data' => ['000000000000000002'],
        ]);
        $changedAt = Carbon::parse('2026-10-19 06:31:54', 'UTC');
        $auditLog = AuditLog::create([
            'validation_batch_id' => $batch->id,
            'batch_row_id' => $row->id,
            'user_id' => $owner->id,
            'column_name' => 'NIP',
            'old_value' => '000000000000000001',
            'new_value' => '000000000000000002',
            'action' => 'correction',
            'notes' => 'Koreksi data sintetis.',
        ]);
        $auditLog->forceFill(['created_at' => $changedAt, 'updated_at' => $changedAt])->save();

        $ownerResponse = $this->actingAs($owner)->get(route('validation.audit', $batch));

        $ownerResponse->assertOk();
        $ownerResponse->assertSee('19/10/2026 13:31 WIB');
        $ownerResponse->assertSee('Petugas Uji');
        $ownerResponse->assertSee('#1');
        $ownerResponse->assertSee('NIP');
        $ownerResponse->assertSee('000000000000000001');
        $ownerResponse->assertSee('000000000000000002');

        $this->actingAs($otherPetugas)
            ->get(route('validation.audit', $batch))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('validation.audit', $batch))
            ->assertOk();
    }

    private function createUser(string $role, string $email): User
    {
        return User::create([
            'name' => $role === 'petugas' ? 'Petugas Uji' : 'Admin Uji',
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
            'headers' => ['NIP'],
        ]);
    }
}
