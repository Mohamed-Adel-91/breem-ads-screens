<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\BrandCarService;
use App\Services\CarInfoService;
use App\Services\ComparisonService;
use Illuminate\Http\Request;


class CarsModuleController extends Controller
{
    public function newCars()
    {
        $brands = Brand::with(['cars' => function ($query) {
            $query->where('show_status', true);
        }])->where('show_status', true)->get();
        return view('web.pages.cars-view.new-cars')->with([
            'brands' => $brands,
        ]);
    }
    public function carInfo($id, $slug, CarInfoService $service)
    {
        $data = $service->build((int) $id);
        if ($slug !== $data['canonical']) {
            return redirect()->route('web.cars.carinfo', [$id, $data['canonical']], 301);
        }
        return view('web.pages.cars-view.carinfo')->with($data);
    }
    public function brandCar($id, $slug, BrandCarService $service)
    {
        $data = $service->build((int) $id);
        if ($slug !== $data['canonical']) {
            return redirect()->route('web.cars.brandcar', [$id, $data['canonical']], 301);
        }
        return view('web.pages.cars-view.brandcar')->with($data);
    }
    public function comparison(Request $request, ComparisonService $service)
    {
        $data = $service->build($request);
        return view('web.pages.cars-view.compare')->with($data);
    }
}
