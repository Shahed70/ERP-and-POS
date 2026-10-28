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
        $this->call(DemoDataSeeder::class);
    }
}
