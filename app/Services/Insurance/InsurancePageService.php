<?php

namespace App\Services\Insurance;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\CarTerm;
use App\Models\Insurance; 
use Illuminate\Http\Request;

class InsurancePageService
{
    /**
     * @return array{
     *  brands:\Illuminate\Support\Collection,
     *  models:\Illuminate\Support\Collection,
     *  terms:\Illuminate\Support\Collection,
     *  programs:\Illuminate\Support\Collection,
     *  selected: array{brandId?:int,modelId?:int,termId?:int,programId?:int,price?:float},
     *  selectedProgram?: \App\Models\Insurance|null
     * }
     */
    public function build(Request $request): array
    {
        $brandId   = $this->toInt($request->query('brandId'));
        $modelId   = $this->toInt($request->query('modelId', $request->query('car_id')));
        $termId    = $this->toInt($request->query('categoryId', $request->query('term_id')));
        $programId = $this->toInt($request->query('programId'));
        $price     = $this->toFloat($request->query('car_price'));

        $brands = Brand::where('show_status', true)->select('id','name')->orderBy('name')->get();

        $models = collect();
        if ($brandId) {
            if ($brand = Brand::find($brandId)) {
                $models = CarModel::where('brand_id',$brand->id)->select('id','name')->orderBy('name')->get();
            }
        }

        $terms = collect();
        if ($modelId) {
            $terms = CarTerm::where('car_model_id',$modelId)->select('id','term_name')->orderBy('term_name')->get();
        }

        $programs = Insurance::select('id','insurance_company','program_name','annual_price')->orderBy('insurance_company')->get();
        $selectedProgram = $programs->firstWhere('id', $programId);

        return [
            'brands'   => $brands,
            'models'   => $models,
            'terms'    => $terms,
            'programs' => $programs,
            'selected' => [
                'brandId'   => $brandId,
                'modelId'   => $modelId,
                'termId'    => $termId,
                'programId' => $programId,
                'price'     => $price,
            ],
            'selectedProgram' => $selectedProgram,
        ];
    }

    private function toInt($v): ?int
    {
        if ($v === null || $v === '') return null;
        return filter_var($v, FILTER_VALIDATE_INT) !== false ? (int)$v : null;
    }
    private function toFloat($v): ?float
    {
        if ($v === null || $v === '') return null;
        return is_numeric($v) ? (float)$v : null;
    }
}
