<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create base users with fixed roles for development
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@gatic.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'name' => 'Editor User',
            'email' => 'editor@gatic.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Editor,
        ]);

        User::factory()->create([
            'name' => 'Lector User',
            'email' => 'lector@gatic.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Lector,
        ]);
    }
}
