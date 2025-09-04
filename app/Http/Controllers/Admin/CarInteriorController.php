<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\CarInteriorRequest;
use App\Models\CarInterior;
use App\Models\CarModel;

class CarInteriorController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $data = CarInterior::with('carModel')->orderByDesc('id')->paginate(25);
        return view('admin.car_module.car_interiors.index')->with([
            'pageName' => 'قائمة الصور الداخليه لسيارة',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.car_interiors.form')->with([
            'pageName' => 'إنشاء صور خارجية لسيارة',
            'models' => CarModel::all(),
        ]);
    }

    public function store(CarInteriorRequest $request)
    {
        $exterior = CarInterior::create($request->validated());
        $folder = CarInterior::UPLOAD_FOLDER;
        $this->fileService->storeFiles($exterior, $request, ['image'], $folder);
        return redirect()->route('admin.car_interiors.index')->with('success', 'تم إنشاء الصور الداخليه للسيارة بنجاح.');
    }

    public function edit($id)
    {
        $data = CarInterior::findOrFail($id);
        return view('admin.car_module.car_interiors.form')->with([
            'pageName' => 'تعديل الصور الداخليه لسيارة',
            'data' => $data,
            'models' => CarModel::all(),
        ]);
    }

    public function update(CarInteriorRequest $request, $id)
    {
        $exterior = CarInterior::findOrFail($id);
        $exterior->update($request->validated());
        $folder = CarInterior::UPLOAD_FOLDER;
        $this->fileService->updateFiles($exterior, $request, ['image'], $folder);
        return redirect()->route('admin.car_interiors.index', $request->query())->with('success', 'تم تحديث الصور الداخليه بنجاح.');
    }

    public function destroy($id)
    {
        $exterior = CarInterior::findOrFail($id);
        $folder = CarInterior::UPLOAD_FOLDER;
        $this->fileService->deleteFile($exterior->image, $folder);
        $exterior->delete();
        return redirect()->route('admin.car_interiors.index')->with('success', 'تم حذف الصور الداخليه بنجاح.');
    }
}
