<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            $permissions = collect([
                'admins.create',
                'admins.delete',
                'admins.edit',
                'admins.view',
                'profile.edit',
                'ads.view',
                'ads.create',
                'ads.edit',
                'ads.approve',
                'ads.delete',
                'ads.schedule',
                'screens.view',
                'screens.create',
                'screens.edit',
                'screens.delete',
                'places.view',
                'places.create',
                'places.edit',
                'places.delete',
                'monitoring.view',
                'monitoring.manage',
                'logs.view',
                'logs.export',
                'reports.view',
                'reports.generate',
                'settings.view',
                'settings.edit',
                'settings.update',
                'users.view',
                'contact_submissions.view',
                'contact_submissions.delete',
                'cms.manage',
                'permissions.view',
                'permissions.create',
                'permissions.edit',
                'permissions.delete',
                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',
            ])->unique()->sort()->values();

            $permissions->each(function (string $permission) {
                Permission::updateOrCreate(
                    ['name' => $permission, 'guard_name' => 'admin'],
                    []
                );
            });

            $permissionNames = $permissions->all();

            $rolePermissionMap = [
                'super-admin' => $permissionNames,
                'admin' => collect($permissionNames)->reject(function (string $permission) {
                    return Str::startsWith($permission, ['permissions.', 'roles.']);
                })->values()->all(),
                'viewer' => collect($permissionNames)->filter(function (string $permission) {
                    return Str::endsWith($permission, '.view');
                })->values()->all(),
            ];

            foreach ($rolePermissionMap as $roleName => $permissionList) {
                $role = Role::updateOrCreate(
                    ['name' => $roleName, 'guard_name' => 'admin'],
                    []
                );
                $role->syncPermissions($permissionList);
            }
        });
    }
}
