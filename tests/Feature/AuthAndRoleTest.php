<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ValidationProfile;
use App\Models\ValidationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_manual_login_form_without_demo_credentials(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Masuk ke Sistem');
        $response->assertSee('Badan Pusat Statistik');
        $response->assertSee('Kabupaten Purwakarta');
        $response->assertSee('Sistem Validasi Data');
        $response->assertSee('images/logo-bps.webp');
        $response->assertSee('Ingat saya di perangkat ini');
        $response->assertSee('data-password-toggle', false);
        $response->assertDontSee('AKUN PENGUJIAN');
        $response->assertDontSee('admin@bps.go.id');
        $response->assertDontSee('petugas@bps.go.id');
        $response->assertDontSee('fillCredentials');
        $response->assertDontSee('value="password"', false);
    }

    public function test_admin_can_login_and_access_user_management(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@bps.go.id',
            'role' => 'admin',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@bps.go.id',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);

        $dashResponse = $this->actingAs($admin)->get('/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Administrator');
        $dashResponse->assertSee('images/logo-bps.webp');
        $dashResponse->assertSee('Profil & Aturan');
        $dashResponse->assertSee('Pengguna');
        $dashResponse->assertSee(route('profiles.index'), false);
        $dashResponse->assertSee(route('users.index'), false);

        $usersResponse = $this->actingAs($admin)->get('/users');
        $usersResponse->assertStatus(200);
        $usersResponse->assertSee('Manajemen Akun Pengguna');

        $profilesResponse = $this->actingAs($admin)->get('/profiles');
        $profilesResponse->assertOk();
    }

    public function test_petugas_can_login_but_cannot_access_user_management(): void
    {
        $petugas = User::create([
            'name' => 'Petugas Test',
            'email' => 'petugas@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'petugas@bps.go.id',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($petugas);

        $dashResponse = $this->actingAs($petugas)->get('/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Petugas BPS');
        $dashResponse->assertSee('images/logo-bps.webp');
        $dashResponse->assertSee('Unggah Excel');
        $dashResponse->assertSee('Riwayat Validasi');
        $dashResponse->assertDontSee(route('profiles.index'), false);
        $dashResponse->assertDontSee(route('users.index'), false);

        // Petugas dilarang mengakses modul kelola user milik Admin
        $usersResponse = $this->actingAs($petugas)->get('/users');
        $usersResponse->assertStatus(403);

        $profilesResponse = $this->actingAs($petugas)->get('/profiles');
        $profilesResponse->assertForbidden();
    }

    public function test_admin_can_create_user_with_explicitly_disabled_status(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-create@bps.go.id',
            'role' => 'admin',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Petugas Nonaktif',
            'email' => 'petugas-nonaktif@bps.go.id',
            'role' => 'petugas',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'petugas-nonaktif@bps.go.id',
            'role' => 'petugas',
            'is_active' => false,
        ]);
    }

    public function test_rule_update_respects_explicit_false_boolean_flags(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-rule@bps.go.id',
            'role' => 'admin',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $profile = ValidationProfile::create([
            'name' => 'Profil Uji',
            'slug' => 'profil-uji',
            'is_active' => true,
        ]);

        $rule = ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'Nama',
            'column_index' => 1,
            'data_type' => 'string',
            'is_required' => true,
            'validation_type' => 'none',
            'parameters' => [],
            'error_message' => 'Wajib diisi',
            'needs_confirmation' => true,
            'confirmation_note' => 'Perlu verifikasi',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('profiles.rules.update', [$profile, $rule]), [
            'error_message' => 'Nama harus terisi dengan benar.',
            'is_required' => false,
            'needs_confirmation' => false,
            'confirmation_note' => 'Tidak perlu verifikasi',
        ]);

        $response->assertRedirect(route('profiles.show', $profile));
        $this->assertDatabaseHas('validation_rules', [
            'id' => $rule->id,
            'is_required' => false,
            'needs_confirmation' => false,
        ]);
    }

    public function test_whatsapp_rule_stays_required_when_updated_by_an_admin(): void
    {
        $admin = User::create([
            'name' => 'Admin WhatsApp',
            'email' => 'admin-whatsapp@bps.go.id',
            'role' => 'admin',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $profile = ValidationProfile::create([
            'name' => 'Data Kepegawaian',
            'slug' => 'data-kepegawaian',
            'is_active' => true,
        ]);
        $rule = ValidationRule::create([
            'validation_profile_id' => $profile->id,
            'column_name' => 'No. WA',
            'column_index' => 19,
            'data_type' => 'string',
            'is_required' => true,
            'validation_type' => 'phone',
            'parameters' => ['pattern' => '/^(\+?62|08)\d{8,13}$/'],
            'error_message' => 'Nomor WhatsApp wajib diisi.',
            'is_active' => true,
        ]);

        $editResponse = $this->actingAs($admin)->get(route('profiles.rules.edit', [$profile, $rule]));
        $editResponse->assertOk();
        $editResponse->assertSee('tidak dapat diubah menjadi opsional');
        $editResponse->assertSee('disabled', false);

        $this->put(route('profiles.rules.update', [$profile, $rule]), [
            'error_message' => 'Nomor WhatsApp wajib diisi.',
            'is_required' => false,
        ])->assertRedirect(route('profiles.show', $profile));

        $this->assertDatabaseHas('validation_rules', [
            'id' => $rule->id,
            'is_required' => true,
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'name' => 'User Nonaktif',
            'email' => 'nonaktif@bps.go.id',
            'role' => 'petugas',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'nonaktif@bps.go.id',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }
}
