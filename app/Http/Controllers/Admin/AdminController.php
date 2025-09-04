<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Models\Admin;

class AdminController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $data = Admin::orderBy('role', 'asc')->paginate(25);
        return view('admin.admins.index')->with([
            'pageName' => 'قائمة المسؤولين',
            'data' => $data,
        ]);
    }

    public function create()
    {
        return view('admin.admins.form')->with([
            'pageName' => 'إنشاء مسؤول جديد',
            'roles' => RolesEnum::asArrayWithDescriptions(),
        ]);
    }

    public function store(AdminRequest $request)
    {
        $validated = $request->validated();
        $admin = Admin::create($validated);
        $folder = Admin::UPLOAD_FOLDER;
        $this->fileService->storeFiles($admin, $request, ['profile_picture'], $folder);
        return redirect()->route('admin.admins.index')->with('success', 'تم إنشاء المسؤول بنجاح.');
    }

    public function edit($id)
    {
        $data = Admin::findOrFail($id);
        return view('admin.admins.form')->with([
            'pageName' => 'تعديل مسؤول',
            'data' => $data,
            'roles' => RolesEnum::asArrayWithDescriptions(),
        ]);
    }

    public function update(AdminRequest $request, $id)
    {
        $admin = Admin::findOrFail($id);
        $validated = $request->validated();
        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        $admin->update($validated);
        $folder = Admin::UPLOAD_FOLDER;
        $this->fileService->updateFiles($admin, $request, ['profile_picture'], $folder);
        return redirect()->route('admin.admins.index', $request->query())
            ->with('success', 'تم تحديث المسؤول بنجاح.');
    }

    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);
        if ($admin->profile_picture) {
            $this->fileService->deleteFile($admin->profile_picture, Admin::UPLOAD_FOLDER);
        }
        $admin->delete();
        return redirect()->route('admin.admins.index')->with('success', 'تم حذف المسؤول بنجاح.');
    }
}
