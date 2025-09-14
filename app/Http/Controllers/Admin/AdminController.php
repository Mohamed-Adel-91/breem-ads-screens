<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
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
        $data = Admin::paginate(25);
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
        $admin->syncRoles($roleNames);
        if ($admin->hasRole('super-admin')) {
            $admin->syncPermissions(Permission::where('guard_name', 'admin')->pluck('name'));
        } else {
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
            'availableRoles' => SpatieRole::where('guard_name', 'admin')->pluck('name', 'id'),
            'availablePermissions' => Permission::where('guard_name', 'admin')->pluck('name', 'id'),
        ]);
    }

    public function update(AdminRequest $request, string $lang, Admin $admin)
    {
        $this->authorizeAdmin('admins.edit');
        $validated = $request->validated();
        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        $admin->update($validated);
        $roleNames = SpatieRole::whereIn('id', $request->input('roles', []))->pluck('name')->toArray();
        $permNames = Permission::whereIn('id', $request->input('permissions', []))->pluck('name')->toArray();
        $admin->syncRoles($roleNames);
        if ($admin->hasRole('super-admin')) {
            $admin->syncPermissions(Permission::where('guard_name', 'admin')->pluck('name'));
        } else {
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
        if ($admin->hasRole('super-admin')) {
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
        /** @var \App\Models\Admin $user */
        $user = Auth::guard('admin')->user();
        abort_if(!$user, 401);

        if (!$user->hasRole('super-admin') && !$user->can($permission)) {
            abort(403);
        }
    }
}
