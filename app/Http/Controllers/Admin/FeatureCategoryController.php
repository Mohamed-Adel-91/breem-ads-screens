<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeatureCategoryRequest;
use App\Models\FeatureCategory;

class FeatureCategoryController extends Controller
{
    public function index()
    {
        $data = FeatureCategory::orderBy('id','desc')->paginate(25);
        return view('admin.car_module.features.features_categories.index')->with([
            'pageName' => 'قائمة فئات المزايا',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.features.features_categories.form')->with([
            'pageName' => 'إنشاء فئة مزايا',
        ]);
    }

    public function store(FeatureCategoryRequest $request)
    {
        FeatureCategory::create($request->validated());
        return redirect()->route('admin.feature_categories.index')->with('success','تم إنشاء فئة الميزة بنجاح.');
    }

    public function edit($id)
    {
        $data = FeatureCategory::findOrFail($id);
        return view('admin.car_module.features.features_categories.form')->with([
            'pageName' => 'تعديل فئة مزايا',
            'data' => $data,
        ]);
    }

    public function update(FeatureCategoryRequest $request, $id)
    {
        $item = FeatureCategory::findOrFail($id);
        $item->update($request->validated());
        return redirect()->route('admin.feature_categories.index', $request->query())->with('success','تم تحديث فئة الميزة بنجاح.');
    }

    public function destroy($id)
    {
        $item = FeatureCategory::findOrFail($id);
        $item->delete();
        return redirect()->route('admin.feature_categories.index')->with('success','تم حذف فئة الميزة بنجاح.');
    }
}
