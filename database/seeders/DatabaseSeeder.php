<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => config('admin.email')],
            [
                'first_name' => config('admin.first_name'),
                'last_name'  => config('admin.last_name'),
                'email'      => config('admin.email'),
                'password'   => config('admin.password'),
                'role'       => (int) config('admin.role'),
            ]
        );
        $this->call(RoleSeeder::class)
            ->call(AdminUserSeeder::class);
    }
}
