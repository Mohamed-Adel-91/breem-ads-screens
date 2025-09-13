<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::where('guard_name', 'admin')->paginate(25);

        return view('admin.roles.index')->with([
            'pageName' => 'قائمة الأدوار',
            'data' => $roles,
        ]);
    }

    public function create()
    {
        $permissions = Permission::where('guard_name', 'admin')->pluck('name', 'id');

        return view('admin.roles.form')->with([
            'pageName' => 'إنشاء دور',
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                Rule::unique('roles', 'name')->where(fn ($q) => $q->where('guard_name', 'admin')),
            ],
            'permissions' => ['array'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'admin',
        ]);

        $permNames = Permission::whereIn('id', $request->input('permissions', []))->pluck('name')->toArray();
        $role->syncPermissions($permNames);

        return redirect()->route('admin.roles.index')->with('success', 'تم إنشاء الدور بنجاح.');
    }

    public function edit($id)
    {
        $role = Role::where('guard_name', 'admin')->findOrFail($id);
        $permissions = Permission::where('guard_name', 'admin')->pluck('name', 'id');

        return view('admin.roles.form')->with([
            'pageName' => 'تعديل دور',
            'data' => $role,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::where('guard_name', 'admin')->findOrFail($id);

        $data = $request->validate([
            'name' => [
                'required',
                Rule::unique('roles', 'name')
                    ->where(fn ($q) => $q->where('guard_name', 'admin'))
                    ->ignore($role->id),
            ],
            'permissions' => ['array'],
        ]);

        $role->update([
            'name' => $data['name'],
        ]);

        $permNames = Permission::whereIn('id', $request->input('permissions', []))->pluck('name')->toArray();
        $role->syncPermissions($permNames);

        return redirect()->route('admin.roles.index')->with('success', 'تم تحديث الدور بنجاح.');
    }

    public function destroy($id)
    {
        $role = Role::where('guard_name', 'admin')->findOrFail($id);
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'تم حذف الدور بنجاح.');
    }
}
