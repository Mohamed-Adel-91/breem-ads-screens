<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ColorRequest;
use App\Models\Color;

class ColorController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $data = Color::orderBy('id','desc')->paginate(25);
        return view('admin.car_module.colors.index')->with([
            'pageName' => 'قائمة الألوان',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.colors.form')->with([
            'pageName' => 'إنشاء لون',
        ]);
    }

    public function store(ColorRequest $request)
    {
        $color = Color::create($request->validated());
        $folder = Color::UPLOAD_FOLDER;
        $this->fileService->storeFiles($color, $request, ['image'], $folder);
        return redirect()->route('admin.colors.index')->with('success','تم إنشاء اللون بنجاح.');
    }

    public function edit($id)
    {
        $data = Color::findOrFail($id);
        return view('admin.car_module.colors.form')->with([
            'pageName' => 'تعديل لون',
            'data' => $data,
        ]);
    }

    public function update(ColorRequest $request, $id)
    {
        $color = Color::findOrFail($id);
        $color->update($request->validated());
        $folder = Color::UPLOAD_FOLDER;
        $this->fileService->updateFiles($color, $request, ['image'], $folder);
        return redirect()->route('admin.colors.index', $request->query())->with('success','تم تحديث اللون بنجاح.');
    }

    public function destroy($id)
    {
        $color = Color::findOrFail($id);
        $folder = Color::UPLOAD_FOLDER;
        $this->fileService->deleteFile($color->image, $folder);
        $color->delete();
        return redirect()->route('admin.colors.index')->with('success','تم حذف اللون بنجاح.');
    }
}
