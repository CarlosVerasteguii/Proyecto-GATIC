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
        User::query()->updateOrCreate(
            ['email' => 'admin@gatic.local'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => UserRole::Admin,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'editor@gatic.local'],
            [
                'name' => 'Editor User',
                'password' => bcrypt('password'),
                'role' => UserRole::Editor,
            ],
        );

        // Second editor for concurrency/lock testing
        User::query()->updateOrCreate(
            ['email' => 'editor2@gatic.local'],
            [
                'name' => 'Editor 2 User',
                'password' => bcrypt('password'),
                'role' => UserRole::Editor,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'lector@gatic.local'],
            [
                'name' => 'Lector User',
                'password' => bcrypt('password'),
                'role' => UserRole::Lector,
            ],
        );

        // Demo inventory data (categories, brands, locations, products, assets, employees)
        $this->call(DemoInventorySeeder::class);

        // Demo pending task for lock testing
        $this->call(DemoPendingTaskSeeder::class);
    }
}
