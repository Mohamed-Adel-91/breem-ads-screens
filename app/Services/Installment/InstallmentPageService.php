<?php

namespace App\Services\Installment;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\InstallmentProgram;
use App\Services\GetCarDetailsService;
use Illuminate\Http\Request;

class InstallmentPageService
{
    public function __construct(
        private GetCarDetailsService $carDetails,
        private InstallmentCalculator $calculator,
    ) {}

    /**
     *
     * @return array{
     *   brands: \Illuminate\Support\Collection,
     *   models: \Illuminate\Support\Collection,
     *   terms: \Illuminate\Support\Collection,
     *   programs: \Illuminate\Support\Collection,
     *   selected: array<string, mixed>,
     *   price: float|null,
     *   summary: array{principal: float, apr: float, monthly: float|null, total: float|null}
     * }
     */
    public function build(Request $request): array
    {
        // ------- Read query (old and new data) -------
        $brandId            = $this->toInt($request->query('brandId'));
        $modelId            = $this->toInt($request->query('modelId', $request->query('car_id')));
        $termId             = $this->toInt($request->query('categoryId', $request->query('term_id')));
        $programId          = $this->toInt($request->query('programId'));
        $tenorMonths        = $this->toInt($request->query('tenorDuration', 0));
        $priceFromQuery     = $this->toFloat($request->query('car_price'));
        $downPaymentAmountQ = $this->toFloat($request->query('down_payment'));
        $downPaymentPercentQ = $this->toFloat($request->query('down_payment_percent'));

        // ------- Base lists -------
        $brands = Brand::query()
            ->where('show_status', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $programs = InstallmentProgram::with('bank:id,name')
            ->select('id', 'bank_id', 'name', 'interest_rate_per_year')
            ->orderBy('name')
            ->get();

        // ------- Dependent lists -------
        $models = collect();
        if ($brandId) {
            if ($brand = Brand::find($brandId)) {
                $models = $this->carDetails->modelsByBrand($brand); // id,name
            }
        }

        $terms = collect();
        if ($modelId) {
            $car = CarModel::select('id', 'start_price')->find($modelId);
            if ($car) {
                $terms = $this->carDetails->termsByCar($car); // id,term_name,(price)
            } else {
                $modelId = null; // invalidate if not found
                $termId  = null;
            }
        }

        // ------- Price (DB-first; fallback للـ Query لو متاح) -------
        $resolvedPrice = null;
        if ($modelId) {
            $resolvedPrice = $this->carDetails->priceForSelection($modelId, $termId ?: null);
        }
        if ($resolvedPrice === null && $priceFromQuery !== null) {
            $resolvedPrice = $priceFromQuery;
        }

        // ------- Down Payment & Percent normalization -------
        $downPaymentAmount  = 0.0;
        $downPaymentPercent = 0.0;
        if ($resolvedPrice !== null) {
            if ($downPaymentAmountQ !== null && $downPaymentAmountQ >= 0) {
                $downPaymentAmount = min($downPaymentAmountQ, $resolvedPrice);
                $downPaymentPercent = $resolvedPrice > 0 ? round(($downPaymentAmount / $resolvedPrice) * 100) : 0.0;
            } elseif ($downPaymentPercentQ !== null && $downPaymentPercentQ >= 0) {
                $downPaymentPercent = max(0.0, min($downPaymentPercentQ, 100.0));
                $downPaymentAmount = round(($downPaymentPercent / 100.0) * $resolvedPrice);
            }
        }

        // ------- APR from selected program -------
        $aprYear = 0.0;
        $selectedProgram = null;
        if ($programId) {
            $selectedProgram = $programs->firstWhere('id', $programId);
            if ($selectedProgram && $selectedProgram->interest_rate_per_year !== null) {
                $aprYear = (float) $selectedProgram->interest_rate_per_year;
            }
        }

        // ------- Summary -------
        $months  = $tenorMonths ?: 0;
        $summary = $this->calculator->summarize(
            $resolvedPrice ?? 0.0,
            $downPaymentAmount,
            $months,
            $aprYear
        );

        // ------- Payload -------
        return [
            'brands'   => $brands,
            'models'   => $models,
            'terms'    => $terms,
            'programs' => $programs,
            'selected' => [
                'brandId'              => $brandId,
                'modelId'              => $modelId,
                'termId'               => $termId,
                'programId'            => $programId,
                'tenor'                => $months,
                'price'                => $resolvedPrice,
                'down_payment'         => $downPaymentAmount,
                'down_payment_percent' => $downPaymentPercent,
            ],
            'price'   => $resolvedPrice,
            'summary' => $summary, // principal, apr, monthly, total
        ];
    }

    private function toInt(mixed $v): ?int
    {
        if ($v === null || $v === '') return null;
        return filter_var($v, FILTER_VALIDATE_INT) !== false ? (int) $v : null;
    }

    private function toFloat(mixed $v): ?float
    {
        if ($v === null || $v === '') return null;
        return is_numeric($v) ? (float) $v : null;
    }
}
