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
        $admin = Admin::updateOrCreate(
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
        $admin->assignRole('super-admin');
        $admin->syncPermissions(Permission::all());
    }
}
