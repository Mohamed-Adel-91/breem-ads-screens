<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        DB::transaction(function () {
            Role::where('name', 'super-admin')
                ->where('guard_name', '!=', 'admin')
                ->update(['guard_name' => 'admin']);
            $perms = ['admins.create', 'admins.edit', 'admins.view', 'profile.edit'];
            foreach ($perms as $p) {
                Permission::updateOrCreate(
                    ['name' => $p, 'guard_name' => 'admin'],
                    []
                );
            }
            $role = Role::updateOrCreate(
                ['name' => 'super-admin', 'guard_name' => 'admin'],
                []
            );
            $role->syncPermissions(Permission::where('guard_name', 'admin')->pluck('id'));
        });
    }
}
