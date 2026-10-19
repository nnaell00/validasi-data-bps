<?php

namespace Tests\Feature;

use App\Models\User;
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
