<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class)
            ->call(AdminUserSeeder::class)
            ->call(HomePageSeeder::class)
            ->call(WhoWeArePageSeeder::class)
            ->call(ContactUsPageSeeder::class)
            ->call(DemoSeeder::class);
    }
}
