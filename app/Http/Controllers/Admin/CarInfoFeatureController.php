<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\CarInfoFeatureRequest;
use App\Models\CarInfoFeature;
use App\Models\CarModel;

class CarInfoFeatureController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $data = CarInfoFeature::with('carModel')->orderByDesc('id')->paginate(25);
        return view('admin.car_module.car_info_features.index')->with([
            'pageName' => 'قائمة مميزات معلومات السيارة',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.car_info_features.form')->with([
            'pageName' => 'إنشاء ميزة معلومات سيارة',
            'models' => CarModel::all(),
        ]);
    }

    public function store(CarInfoFeatureRequest $request)
    {
        $feature = CarInfoFeature::create($request->validated());
        $folder = CarInfoFeature::UPLOAD_FOLDER;
        $this->fileService->storeFiles($feature, $request, ['image'], $folder);
        return redirect()->route('admin.car_info_features.index')->with('success', 'تم إنشاء ميزة معلومات السيارة بنجاح.');
    }

    public function edit($id)
    {
        $data = CarInfoFeature::findOrFail($id);
        return view('admin.car_module.car_info_features.form')->with([
            'pageName' => 'تعديل ميزة معلومات سيارة',
            'data' => $data,
            'models' => CarModel::all(),
        ]);
    }

    public function update(CarInfoFeatureRequest $request, $id)
    {
        $feature = CarInfoFeature::findOrFail($id);
        $feature->update($request->validated());
        $folder = CarInfoFeature::UPLOAD_FOLDER;
        $this->fileService->updateFiles($feature, $request, ['image'], $folder);
        return redirect()->route('admin.car_info_features.index', $request->query())->with('success', 'تم تحديث ميزة معلومات السيارة بنجاح.');
    }

    public function destroy($id)
    {
        $feature = CarInfoFeature::findOrFail($id);
        $folder = CarInfoFeature::UPLOAD_FOLDER;
        $this->fileService->deleteFile($feature->image, $folder);
        $feature->delete();
        return redirect()->route('admin.car_info_features.index')->with('success', 'تم حذف ميزة معلومات السيارة بنجاح.');
    }
}
