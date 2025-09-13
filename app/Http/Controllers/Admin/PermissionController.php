<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\RoutesHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::where('guard_name', 'admin')->paginate(25);

        return view('admin.permissions.index')->with([
            'pageName' => 'قائمة الصلاحيات',
            'data' => $permissions,
        ]);
    }

    public function create()
    {
        return view('admin.permissions.form')->with([
            'pageName' => 'إنشاء صلاحية',
            'routes' => RoutesHelper::getAdminRouteNames(),
        ]);
    }

    public function store(Request $request)
    {
        $routes = RoutesHelper::getAdminRouteNames();

        $data = $request->validate([
            'name' => [
                'required',
                Rule::in($routes),
                Rule::unique('permissions', 'name')->where(fn ($q) => $q->where('guard_name', 'admin')),
            ],
        ]);

        Permission::create([
            'name' => $data['name'],
            'guard_name' => 'admin',
        ]);

        return redirect()->route('admin.permissions.index')->with('success', 'تم إنشاء الصلاحية بنجاح.');
    }

    public function edit($id)
    {
        $permission = Permission::where('guard_name', 'admin')->findOrFail($id);

        return view('admin.permissions.form')->with([
            'pageName' => 'تعديل صلاحية',
            'routes' => RoutesHelper::getAdminRouteNames(),
            'data' => $permission,
        ]);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::where('guard_name', 'admin')->findOrFail($id);
        $routes = RoutesHelper::getAdminRouteNames();

        $data = $request->validate([
            'name' => [
                'required',
                Rule::in($routes),
                Rule::unique('permissions', 'name')
                    ->where(fn ($q) => $q->where('guard_name', 'admin'))
                    ->ignore($permission->id),
            ],
        ]);

        $permission->update([
            'name' => $data['name'],
        ]);

        return redirect()->route('admin.permissions.index')->with('success', 'تم تحديث الصلاحية بنجاح.');
    }

    public function destroy($id)
    {
        $permission = Permission::where('guard_name', 'admin')->findOrFail($id);
        $permission->delete();

        return redirect()->route('admin.permissions.index')->with('success', 'تم حذف الصلاحية بنجاح.');
    }
}
