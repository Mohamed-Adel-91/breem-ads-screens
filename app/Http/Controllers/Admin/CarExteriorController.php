<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\CarExteriorRequest;
use App\Models\CarExterior;
use App\Models\CarModel;

class CarExteriorController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $data = CarExterior::with('carModel')->orderByDesc('id')->paginate(25);
        return view('admin.car_module.car_exteriors.index')->with([
            'pageName' => 'قائمة الصور الخارجية لسيارة',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.car_exteriors.form')->with([
            'pageName' => 'إنشاء صور خارجية لسيارة',
            'models' => CarModel::all(),
        ]);
    }

    public function store(CarExteriorRequest $request)
    {
        $exterior = CarExterior::create($request->validated());
        $folder = CarExterior::UPLOAD_FOLDER;
        $this->fileService->storeFiles($exterior, $request, ['image'], $folder);

        return redirect()->route('admin.car_exteriors.index')->with('success', 'تم إنشاء الصور الخارجية للسيارة بنجاح.');
    }

    public function edit($id)
    {
        $data = CarExterior::findOrFail($id);
        return view('admin.car_module.car_exteriors.form')->with([
            'pageName' => 'تعديل الصور الخارجية لسيارة',
            'data' => $data,
            'models' => CarModel::all(),
        ]);
    }

    public function update(CarExteriorRequest $request, $id)
    {
        $exterior = CarExterior::findOrFail($id);
        $exterior->update($request->validated());
        $folder = CarExterior::UPLOAD_FOLDER;
        $this->fileService->updateFiles($exterior, $request, ['image'], $folder);
        return redirect()->route('admin.car_exteriors.index', $request->query())->with('success', 'تم تحديث الصور الخارجية بنجاح.');
    }

    public function destroy($id)
    {
        $exterior = CarExterior::findOrFail($id);
        $folder = CarExterior::UPLOAD_FOLDER;
        $this->fileService->deleteFile($exterior->image, $folder);
        $exterior->delete();
        return redirect()->route('admin.car_exteriors.index')->with('success', 'تم حذف الصور الخارجية بنجاح.');
    }
}
