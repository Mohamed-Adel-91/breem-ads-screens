<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission;


class AdminController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $this->authorizeAdmin('admins.view');
        $data = Admin::orderBy('role', 'asc')->paginate(25);
        return view('admin.admins.index')->with([
            'pageName' => 'قائمة المسؤولين',
            'data' => $data,
        ]);
    }

    public function create()
    {
        $this->authorizeAdmin('admins.create');
        return view('admin.admins.form')->with([
            'pageName' => 'إنشاء مسؤول جديد',
            'roles' => RolesEnum::asArrayWithDescriptions(),
            'availableRoles' => SpatieRole::where('guard_name', 'admin')->pluck('name', 'id'),
            'availablePermissions' => Permission::where('guard_name', 'admin')->pluck('name', 'id'),
        ]);
    }

    public function store(AdminRequest $request)
    {
        $this->authorizeAdmin('admins.create');
        $validated = $request->validated();
        $admin = Admin::create($validated);
        $roleNames = SpatieRole::whereIn('id', $request->input('roles', []))->pluck('name')->toArray();
        $permNames = Permission::whereIn('id', $request->input('permissions', []))->pluck('name')->toArray();
        if ($admin->id === 1) {
            $admin->syncRoles(['super-admin']);
            $admin->syncPermissions(Permission::where('guard_name', 'admin')->pluck('name'));
        } else {
            $admin->syncRoles($roleNames);
            $admin->syncPermissions($permNames);
        }
        $folder = Admin::UPLOAD_FOLDER;
        $this->fileService->storeFiles($admin, $request, ['profile_picture'], $folder);
        return redirect()->route('admin.admins.index')->with('success', 'تم إنشاء المسؤول بنجاح.');
    }

    public function edit(string $lang, Admin $admin)
    {
        $this->authorizeAdmin('admins.edit');
        return view('admin.admins.form')->with([
            'pageName' => 'تعديل مسؤول',
            'data' => $admin,
            'roles' => RolesEnum::asArrayWithDescriptions(),
            'availableRoles' => SpatieRole::where('guard_name', 'admin')->pluck('name', 'id'),
            'availablePermissions' => Permission::where('guard_name', 'admin')->pluck('name', 'id'),
        ]);
    }

    public function update(AdminRequest $request, string $lang, Admin $admin)
    {
        $this->authorizeAdmin('admins.edit');
        $validated = $request->validated();
        if ($admin->id === 1 && isset($validated['role']) && (int) $validated['role'] !== RolesEnum::SUPER_ADMIN) {
            return redirect()->route('admin.admins.index', $request->query())
                ->with('error', 'لا يمكن إزالة دور super-admin من المسؤول الرئيسي.');
        }
        if ($admin->id === 1) {
            $validated['role'] = RolesEnum::SUPER_ADMIN;
        }
        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        $admin->update($validated);
        if ($admin->id === 1) {
            $admin->syncRoles(['super-admin']);
            $admin->syncPermissions(Permission::where('guard_name', 'admin')->pluck('name'));
        } else {
            $roleNames = SpatieRole::whereIn('id', $request->input('roles', []))->pluck('name')->toArray();
            $permNames = Permission::whereIn('id', $request->input('permissions', []))->pluck('name')->toArray();
            $admin->syncRoles($roleNames);
            $admin->syncPermissions($permNames);
        }
        $folder = Admin::UPLOAD_FOLDER;
        $this->fileService->updateFiles($admin, $request, ['profile_picture'], $folder);
        return redirect()->route('admin.admins.index', $request->query())
            ->with('success', 'تم تحديث المسؤول بنجاح.');
    }

    public function destroy(string $lang, Admin $admin)
    {
        $this->authorizeAdmin('admins.edit');
        if ($admin->id === 1) {
            return redirect()->route('admin.admins.index')->with('error', 'لا يمكن حذف المسؤول الرئيسي.');
        }
        if ($admin->profile_picture) {
            $this->fileService->deleteFile($admin->profile_picture, Admin::UPLOAD_FOLDER);
        }
        $admin->delete();
        return redirect()->route('admin.admins.index')->with('success', 'تم حذف المسؤول بنجاح.');
    }

    protected function authorizeAdmin(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        if ($user->id !== 1 && !Gate::forUser($user)->allows($permission)) {
            abort(403);
        }
    }
}
