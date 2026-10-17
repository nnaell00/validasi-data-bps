<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

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

        $usersResponse = $this->actingAs($admin)->get('/users');
        $usersResponse->assertStatus(200);
        $usersResponse->assertSee('Manajemen Akun Pengguna');
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

        // Petugas dilarang mengakses modul kelola user milik Admin
        $usersResponse = $this->actingAs($petugas)->get('/users');
        $usersResponse->assertStatus(403);
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
