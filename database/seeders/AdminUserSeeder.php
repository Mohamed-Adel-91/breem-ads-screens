<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@company.com'],
            ['name' => 'Super Admin', 'password' => bcrypt('StrongPass123')]
        );
        $user->assignRole('super-admin');
    }
}
