<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ValidationBatch;
use App\Models\ValidationProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ValidationBatchPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_every_session_while_petugas_are_limited_to_their_own(): void
    {
        $admin = $this->createUser('admin', 'admin@bps.go.id');
        $owner = $this->createUser('petugas', 'pemilik@bps.go.id');
        $otherPetugas = $this->createUser('petugas', 'petugas-lain@bps.go.id');
        $unsupportedUser = $this->createUser('auditor', 'auditor@bps.go.id');
        $batch = $this->createBatch($owner);

        $this->assertTrue(Gate::forUser($admin)->allows('view', $batch));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $batch));
        $this->assertTrue(Gate::forUser($owner)->allows('view', $batch));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $batch));
        $this->assertFalse(Gate::forUser($otherPetugas)->allows('view', $batch));
        $this->assertFalse(Gate::forUser($otherPetugas)->allows('update', $batch));
        $this->assertFalse(Gate::forUser($unsupportedUser)->allows('view', $batch));
    }

    private function createUser(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role).' Uji',
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
            'total_rows' => 0,
            'status' => 'uploaded',
            'headers' => [],
        ]);
    }
}
