<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@bps.go.id'],
            [
                'name' => 'Administrator BPS',
                'role' => 'admin',
                'nip' => '198501012010011001',
                'phone' => '081234567890',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'petugas@bps.go.id'],
            [
                'name' => 'Petugas BPS Purwakarta',
                'role' => 'petugas',
                'nip' => '199203152015022002',
                'phone' => '081298765432',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $this->call(ValidationProfileSeeder::class);
    }
}
