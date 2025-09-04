<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeatureRequest;
use App\Models\Feature;
use App\Models\FeatureCategory;

class FeatureController extends Controller
{
    public function index()
    {
        $data = Feature::with('category')->orderBy('id','desc')->paginate(25);
        return view('admin.car_module.features.index')->with([
            'pageName' => 'قائمة المزايا',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.features.form')->with([
            'pageName' => 'إنشاء ميزة',
            'categories' => FeatureCategory::all(),
        ]);
    }

    public function store(FeatureRequest $request)
    {
        Feature::create($request->validated());
        return redirect()->route('admin.features.index')->with('success','تم إنشاء الميزة بنجاح.');
    }

    public function edit($id)
    {
        $data = Feature::findOrFail($id);
        return view('admin.car_module.features.form')->with([
            'pageName' => 'تعديل ميزة',
            'data' => $data,
            'categories' => FeatureCategory::all(),
        ]);
    }

    public function update(FeatureRequest $request, $id)
    {
        $item = Feature::findOrFail($id);
        $item->update($request->validated());
        return redirect()->route('admin.features.index', $request->query())->with('success','تم تحديث الميزة بنجاح.');
    }

    public function destroy($id)
    {
        $item = Feature::findOrFail($id);
        $item->delete();
        return redirect()->route('admin.features.index')->with('success','تم حذف الميزة بنجاح.');
    }
}
