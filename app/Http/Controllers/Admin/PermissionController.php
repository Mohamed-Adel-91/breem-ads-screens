<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
}
