<?php
namespace Database\Seeders;
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = Admin::updateOrCreate(
            ['email' => config('admin.email')],
            [
                'first_name' => config('admin.first_name'),
                'last_name'  => config('admin.last_name'),
                'email'      => config('admin.email'),
                'password'   => config('admin.password'),
            ]
        );
        $role = Role::where('name', 'super-admin')->where('guard_name', 'admin')->first();
        if (!$role) {
            $role = Role::create(['name' => 'super-admin', 'guard_name' => 'admin']);
        }
        $superAdmin->assignRole('super-admin');
        $superAdmin->syncPermissions(Permission::where('guard_name', 'admin')->get());

        $cmsOnlyAdmin = Admin::updateOrCreate(
            ['email' => 'website.cms@breem.com'],
            [
                'first_name' => config('admin.cms_first_name'),
                'last_name'  => config('admin.cms_last_name'),
                'email'      => config('admin.cms_email'),
                'password'   => config('admin.cms_password'),
            ]
        );

        $cmsPermissions = Permission::where('guard_name', 'admin')
            ->whereIn('name', [
                'cms.manage',
                'contact_submissions.view',
                'contact_submissions.delete',
            ])->get();

        $cmsOnlyAdmin->syncPermissions($cmsPermissions);
    }
}
