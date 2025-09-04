<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ShapeRequest;
use App\Models\Shape;

class ShapeController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $data = Shape::orderBy('id','desc')->paginate(25);
        return view('admin.car_module.shapes.index')->with([
            'pageName' => 'قائمة الأشكال',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.shapes.form')->with([
            'pageName' => 'إنشاء شكل',
        ]);
    }

    public function store(ShapeRequest $request)
    {
        $shape = Shape::create($request->validated());
        $folder = Shape::UPLOAD_FOLDER;
        $this->fileService->storeFiles($shape, $request, ['logo'], $folder);
        return redirect()->route('admin.shapes.index')->with('success','تم إنشاء الشكل بنجاح.');
    }

    public function edit($id)
    {
        $data = Shape::findOrFail($id);
        return view('admin.car_module.shapes.form')->with([
            'pageName' => 'تعديل شكل',
            'data' => $data,
        ]);
    }

    public function update(ShapeRequest $request, $id)
    {
        $shape = Shape::findOrFail($id);
        $shape->update($request->validated());
        $folder = Shape::UPLOAD_FOLDER;
        $this->fileService->updateFiles($shape, $request, ['logo'], $folder);
        return redirect()->route('admin.shapes.index', $request->query())->with('success','تم تحديث الشكل بنجاح.');
    }

    public function destroy($id)
    {
        $shape = Shape::findOrFail($id);
        $folder = Shape::UPLOAD_FOLDER . $shape->id;
        $this->fileService->deleteFile($shape->logo, $folder);
        $shape->delete();
        return redirect()->route('admin.shapes.index')->with('success','تم حذف الشكل بنجاح.');
    }
}
