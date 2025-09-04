<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\CarModelRequest;
use App\Models\CarModel;
use App\Models\CarModelColor;
use App\Models\Brand;
use App\Models\Shape;
use App\Models\Color;

class CarModelController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $data = CarModel::with(['brand', 'shape'])->orderBy('id', 'desc')->paginate(25);
        return view('admin.car_module.car_models.index')->with([
            'pageName' => 'قائمة موديلات السيارات',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.car_models.form')->with([
            'pageName' => 'إنشاء موديل سيارة',
            'brands' => Brand::all(),
            'shapes' => Shape::all(),
            'colors' => Color::all(),
        ]);
    }

    public function store(CarModelRequest $request)
    {
        $validated = $request->validated();
        $model = CarModel::create($validated);
        $baseFolder = CarModel::UPLOAD_FOLDER;
        $this->fileService->storeFiles($model, $request, ['image', 'catalog', 'maintenance_schedule_pdf', 'banner','banner_tablet', 'banner_mobile'], $baseFolder);
        $folder = $baseFolder;
        $colorsData = [];
        foreach ($request->input('colors', []) as $colorId) {
            $pivotImage = null;
            if ($request->hasFile("color_images.$colorId")) {
                $upload = $this->uploadFile([
                    $request->file("color_images.$colorId")
                ], [$folder . '/colors'], ['image']);
                $pivotImage = $upload[0] ?? null;
            }
            $colorsData[$colorId] = ['image' => $pivotImage];
        }
        if ($colorsData) {
            $model->colors()->attach($colorsData);
        }
        return redirect()->route('admin.car_models.index')->with('success', 'تم إنشاء موديل السيارة بنجاح.');
    }

    public function edit($id)
    {
        $data = CarModel::findOrFail($id);
        return view('admin.car_module.car_models.form')->with([
            'pageName' => 'تعديل موديل سيارة',
            'data' => $data,
            'brands' => Brand::all(),
            'shapes' => Shape::all(),
            'colors' => Color::all(),
        ]);
    }

    public function update(CarModelRequest $request, $id)
    {
        $model = CarModel::findOrFail($id);
        $model->update($request->validated());
        $baseFolder = CarModel::UPLOAD_FOLDER;
        $this->fileService->updateFiles($model, $request, ['image', 'catalog', 'maintenance_schedule_pdf', 'banner','banner_tablet', 'banner_mobile'], $baseFolder);

        $folder = $baseFolder;
        $requestedColors = $request->input('colors', []);
        $colorsData = [];
        foreach ($requestedColors as $colorId) {
            $existing = $model->colors()->where('colors.id', $colorId)->first();
            $pivotImage = $existing->pivot->image ?? null;
            if ($request->hasFile("color_images.$colorId")) {
                if ($pivotImage) {
                    $this->fileService->deleteFile($pivotImage, $folder . '/colors');
                }
                $upload = $this->uploadFile([
                    $request->file("color_images.$colorId")
                ], [$folder . '/colors'], ['image']);
                $pivotImage = $upload[0] ?? null;
            }
            $colorsData[$colorId] = ['image' => $pivotImage];
        }

        $removed = $model->colors()->whereNotIn('colors.id', $requestedColors)->get();
        foreach ($removed as $color) {
            $this->fileService->deleteFile($color->pivot->image, $folder . '/colors');
        }

        $model->colors()->sync($colorsData);
        return redirect()->route('admin.car_models.index', $request->query())->with('success', 'تم تحديث موديل السيارة بنجاح.');
    }

    public function destroy($id)
    {
        $model = CarModel::findOrFail($id);
        $folder = CarModel::UPLOAD_FOLDER;
        $this->fileService->deleteFile($model->image, $folder);
        $this->fileService->deleteFile($model->catalog, $folder);
        $this->fileService->deleteFile($model->maintenance_schedule_pdf, $folder);
        $this->fileService->deleteFile($model->banner, $folder);
        $this->fileService->deleteFile($model->banner_tablet, $folder);
        $this->fileService->deleteFile($model->banner_mobile, $folder);
        $model->delete();
        return redirect()->route('admin.car_models.index')->with('success', 'تم حذف موديل السيارة بنجاح.');
    }
}
