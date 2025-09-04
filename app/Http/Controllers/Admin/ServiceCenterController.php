<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCenterRequest;
use App\Models\ServiceCenter;
use App\Models\Brand;
use App\Enums\CityEnum;

class ServiceCenterController extends Controller
{
    public function index()
    {
        $data = ServiceCenter::with('brand')->orderBy('id','desc')->paginate(10);
        return view('admin.car_module.service_centers.index')->with([
            'pageName' => 'قائمة مراكز الخدمة',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.service_centers.form')->with([
            'pageName' => 'إنشاء مركز خدمة',
            'data'     => new ServiceCenter(),
            'brands' => Brand::all(),
            'cities' => CityEnum::asArrayWithDescriptions(),
        ]);
    }

    public function store(ServiceCenterRequest $request)
    {
        ServiceCenter::create($request->validated());
        return redirect()->route('admin.service_centers.index')->with('success','تم إنشاء مركز الخدمة بنجاح.');
    }

    public function edit($id)
    {
        $data = ServiceCenter::findOrFail($id);
        return view('admin.car_module.service_centers.form')->with([
            'pageName' => 'تعديل مركز خدمة',
            'data' => $data,
            'brands' => Brand::all(),
            'cities' => CityEnum::asArrayWithDescriptions(),
        ]);
    }

    public function update(ServiceCenterRequest $request, $id)
    {
        $item = ServiceCenter::findOrFail($id);
        $item->update($request->validated());
        return redirect()->route('admin.service_centers.index', $request->query())->with('success','تم تحديث مركز الخدمة بنجاح.');
    }

    public function destroy($id)
    {
        $item = ServiceCenter::findOrFail($id);
        $item->delete();
        return redirect()->route('admin.service_centers.index')->with('success','تم حذف مركز الخدمة بنجاح.');
    }
}
