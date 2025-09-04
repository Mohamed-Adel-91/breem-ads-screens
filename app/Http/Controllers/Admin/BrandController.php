<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;

class BrandController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $data = Brand::orderBy('id', 'desc')->paginate(25);
        return view('admin.car_module.brands.index')->with([
            'pageName' => 'قائمة الماركات',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.brands.form')->with([
            'pageName' => 'إنشاء ماركة',
        ]);
    }

    public function store(BrandRequest $request)
    {
        $validated = $request->validated();
        $sliderImages = $request->file('slider_images', []);
        unset($validated['slider_images']);
        $brand = Brand::create($validated);
        $folder = Brand::UPLOAD_FOLDER;
        $this->fileService->storeFiles($brand, $request, [
            'logo',
            'banner',
            'banner_tablet',
            'banner_mobile',
        ], $folder);
        $uploadedSliderImages = [];
        foreach ($sliderImages as $image) {
            $upload = $this->uploadFile([$image], [$folder]);
            if (!empty($upload[0])) {
                $uploadedSliderImages[] = $upload[0];
            }
        }

        if ($uploadedSliderImages) {
            $brand->slider_images = $uploadedSliderImages;
            $brand->save();
        }
        return redirect()->route('admin.brands.index')->with('success', 'تم إنشاء الماركة بنجاح.');
    }

    public function edit($id)
    {
        $data = Brand::findOrFail($id);
        return view('admin.car_module.brands.form')->with([
            'pageName' => 'تعديل ماركة',
            'data' => $data,
        ]);
    }

    public function update(BrandRequest $request, $id)
    {
        $brand = Brand::findOrFail($id);
        $validated = $request->validated();
        unset($validated['slider_images'], $validated['delete_slider_images']);
        $brand->update($validated);
        $folder = Brand::UPLOAD_FOLDER;
        $this->fileService->updateFiles($brand, $request, [
            'logo',
            'banner',
            'banner_tablet',
            'banner_mobile',
        ], $folder);
        $existingImages = $brand->slider_images ?? [];
        $deleteImages = $request->input('delete_slider_images', []);
        if ($deleteImages) {
            foreach ($deleteImages as $image) {
                $this->fileService->deleteFile($image, $folder);
            }
            $existingImages = array_values(array_diff($existingImages, $deleteImages));
        }
        $newImages = [];
        foreach ($request->file('slider_images', []) as $image) {
            $upload = $this->uploadFile([$image], [$folder]);
            if (!empty($upload[0])) {
                $newImages[] = $upload[0];
            }
        }
        $brand->slider_images = array_merge($existingImages, $newImages);
        $brand->save();
        return redirect()->route('admin.brands.index', $request->query())->with('success', 'تم تحديث الماركة بنجاح.');
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        $folder = Brand::UPLOAD_FOLDER;
        foreach ((array) $brand->slider_images as $image) {
            $this->fileService->deleteFile($image, $folder);
        }
        $this->fileService->deleteFile($brand->logo, $folder);
        $this->fileService->deleteFile($brand->banner, $folder);
        $this->fileService->deleteFile($brand->banner_tablet, $folder);
        $this->fileService->deleteFile($brand->banner_mobile, $folder);
        $brand->delete();
        return redirect()->route('admin.brands.index')->with('success', 'تم حذف الماركة بنجاح.');
    }

    public function toggleStatus(Brand $brand)
    {
        $brand->show_status = !$brand->show_status;
        $brand->save();
        return response()->json(['show_status' => $brand->show_status]);
    }
}
