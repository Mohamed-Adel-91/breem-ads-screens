<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarTermRequest;
use App\Models\CarTerm;
use App\Models\CarModel;
use App\Models\Feature;

class CarTermController extends Controller
{
    public function index()
    {
        $data = CarTerm::with('model')->orderBy('id', 'desc')->paginate(25);
        return view('admin.car_module.car_terms.index')->with([
            'pageName' => 'قائمة فئات السيارات',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.car_terms.form')->with([
            'pageName' => 'إنشاء فئة سيارة',
            'models' => CarModel::all(),
            'features' => Feature::all(),
        ]);
    }

    public function store(CarTermRequest $request)
    {
        $validated = $request->validated();
        $carTerm = CarTerm::create($validated);

        foreach ($request->input('specs', []) as $key => $value) {
            if ($value !== null && $value !== '') {
                $carTerm->specs()->create(['value' => $value, 'status' => true]);
            }
        }
        $featuresData = [];
        foreach ($request->input('features', []) as $featureId) {
            $featuresData[$featureId] = [
                'value' => $request->input('feature_values.' . $featureId),
                'priority' => $request->input('feature_priorities.' . $featureId),
                'status' => $request->boolean('feature_statuses.' . $featureId),
            ];
        }
        if ($featuresData) {
            $carTerm->features()->attach($featuresData);
        }
        return redirect()->route('admin.car_terms.index')->with('success', 'تم إنشاء فئة السيارة بنجاح.');
    }

    public function edit($id)
    {
        $data = CarTerm::findOrFail($id);
        return view('admin.car_module.car_terms.form')->with([
            'pageName' => 'تعديل فئة سيارة',
            'data' => $data,
            'models' => CarModel::all(),
            'features' => Feature::all(),
        ]);
    }

    public function update(CarTermRequest $request, $id)
    {
        $item = CarTerm::findOrFail($id);
        $item->update($request->validated());

        $specs = $request->input('specs', []);
        foreach ($specs as $specId => $value) {
            if (str_starts_with((string)$specId, 'new_')) {
                if ($value !== null && $value !== '') {
                    $item->specs()->create(['value' => $value, 'status' => true]);
                }
            } else {
                $spec = $item->specs()->where('id', $specId)->first();
                if ($spec) {
                    $spec->update(['value' => $value]);
                }
            }
        }

        $deleteSpecs = $request->input('delete_specs', []);
        if ($deleteSpecs) {
            $item->specs()->whereIn('id', $deleteSpecs)->delete();
        }

        $requestedFeatures = $request->input('features', []);
        $featuresData = [];
        foreach($requestedFeatures as $featureId){
            $featuresData[$featureId] = [
                'value' => $request->input('feature_values.' . $featureId),
                'priority' => $request->input('feature_priorities.' . $featureId),
                'status' => $request->boolean('feature_statuses.' . $featureId),
            ];
        }
        $item->features()->sync($featuresData);
        return redirect()->route('admin.car_terms.index', $request->query())->with('success', 'تم تحديث فئة السيارة بنجاح.');
    }

    public function destroy($id)
    {
        $item = CarTerm::findOrFail($id);
        $item->delete();
        return redirect()->route('admin.car_terms.index')->with('success', 'تم حذف فئة السيارة بنجاح.');
    }
}
