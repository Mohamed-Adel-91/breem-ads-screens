<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\RoutesHelper;
use App\Http\Controllers\Controller;
use App\Support\Lang;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::where('guard_name', 'admin')->paginate(25);

        return view('admin.permissions.index')->with([
            'pageName' => Lang::t('admin.pages.permissions.index', 'قائمة الصلاحيات'),
            'data' => $permissions,
        ]);
    }

    public function create()
    {
        return view('admin.permissions.form')->with([
            'pageName' => Lang::t('admin.pages.permissions.create', 'إنشاء صلاحية'),
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

        return redirect()->route('admin.permissions.index')
            ->with('success', Lang::t('admin.flash.permissions.created', 'تم إنشاء الصلاحية بنجاح.'));
    }

    public function edit(string $lang, Permission $permission)
    {
        abort_unless($permission->guard_name === 'admin', 404);

        return view('admin.permissions.form')->with([
            'pageName' => Lang::t('admin.pages.permissions.edit', 'تعديل صلاحية'),
            'routes' => RoutesHelper::getAdminRouteNames(),
            'data' => $permission,
        ]);
    }

    public function update(Request $request, string $lang, Permission $permission)
    {
        abort_unless($permission->guard_name === 'admin', 404);
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

        return redirect()->route('admin.permissions.index')
            ->with('success', Lang::t('admin.flash.permissions.updated', 'تم تحديث الصلاحية بنجاح.'));
    }

    public function destroy(string $lang, Permission $permission)
    {
        abort_unless($permission->guard_name === 'admin', 404);
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', Lang::t('admin.flash.permissions.deleted', 'تم حذف الصلاحية بنجاح.'));
    }
}
