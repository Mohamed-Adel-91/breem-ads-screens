<?php

namespace Database\Seeders;

use App\Models\Admin;
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
        $admin = Admin::updateOrCreate(
            ['email' => config('admin.email')],
            [
                'first_name' => config('admin.first_name'),
                'last_name'  => config('admin.last_name'),
                'email'      => config('admin.email'),
                'password'   => config('admin.password'),
                'role'       => (int) config('admin.role'),
            ]
        );
        $admin->assignRole('super-admin');
    }
}
