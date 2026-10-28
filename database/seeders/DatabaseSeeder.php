<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with essential roles and the initial administrator.
     * Absolutely no fake/mock business data is created.
     */
    public function run(): void
    {
        // Essential system roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $staffRole = Role::firstOrCreate(['name' => 'Staff']);

        // Default Owner/Admin account
        $admin = User::firstOrCreate(
            ['email' => 'admin@fishfeed.com'],
            [
                'name' => 'Shop Owner',
                'password' => Hash::make('password123'),
            ]
        );

        if (! $admin->hasRole('Admin')) {
            $admin->assignRole($adminRole);
        }
    }
}
