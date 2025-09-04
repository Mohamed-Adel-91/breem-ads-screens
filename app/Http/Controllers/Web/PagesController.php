<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use App\Services\GetCarDetailsService;
use App\Services\PagesService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class PagesController extends Controller
{
    public function __construct(
        private PagesService $pages,
        private SearchService $search,
        private GetCarDetailsService $details,
    ) {}
    public function index(Request $request)
    {
        return view('web.pages.index')->with($this->pages->home());
    }
    public function videos()
    {
        return view('web.pages.videos')->with($this->pages->videos());
    }
    public function serviceCenters(Request $request)
    {
        return view('web.pages.service-centers')->with($this->pages->serviceCenters());
    }
    public function faqs(Request $request)
    {
        return view('web.pages.faqs');
    }
    public function mobileSearch(Request $request)
    {
        return view('web.pages.mobile-search-page')->with($this->pages->search());
    }
    public function searchResult(Request $request)
    {
        $cars = $this->search->search([
            'brandId'   => $request->query('brandId'),
            'modelId'   => $request->query('modelId'),
            'termId'    => $request->query('categoryId'),
            'priceFrom' => $request->query('priceFrom'),
            'priceTo'   => $request->query('priceTo'),
        ]);
        return view('web.pages.cars-view.search-page')->with(['cars' => $cars]);
    }
    public function modelsByBrand(Brand $brand)
    {
        try {
            $models = $this->details->modelsByBrand($brand);
            Log::info("Models fetched for brand {$brand->id}: " . $models->count());
            return response()->json([
                'success' => true,
                'models' => $models
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching models for brand {$brand->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الموديلات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function termsByCar(CarModel $car)
    {
        try {
            $terms = $this->details->termsByCar($car);
            Log::info("Terms fetched for car {$car->id}: " . $terms->count());
            return response()->json([
                'success' => true,
                'terms' => $terms
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching terms for car {$car->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الفئات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function priceForSelection(Request $request)
    {
        Log::info('Price request received:', $request->all());
        $validator = Validator::make($request->all(), [
            'car_id' => 'required|integer|exists:car_models,id',
            'term_id' => 'nullable|integer|exists:car_terms,id',
            'program_id' => 'nullable|integer',
            'insurance_id' => 'nullable|integer',
            'car_price' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            Log::warning('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors(),
            ], 422);
        }
        try {
            $validation = $this->details->validateCarAndTerm(
                (int) $request->car_id,
                $request->filled('term_id') ? (int) $request->term_id : null
            );
            if (!$validation['valid']) {
                Log::warning("Invalid car/term combination: " . $validation['message']);
                return response()->json([
                    'success' => false,
                    'message' => $validation['message'],
                    'price' => null
                ], 404);
            }
            $price = $this->details->priceForSelection(
                (int) $request->car_id,
                $request->filled('term_id') ? (int) $request->term_id : null,
                $request->filled('program_id') ? (int) $request->program_id : null,
                $request->filled('insurance_id') ? (int) $request->insurance_id : null,
                $request->filled('car_price') ? (float) $request->car_price : null,
            );
            if ($price === null) {
                Log::warning("Price not found for car_id: {$request->car_id}, term_id: {$request->term_id}");
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على سعر لهذه السيارة',
                    'price' => null
                ], 404);
            }
            Log::info("Price found: {$price} for car_id: {$request->car_id}, term_id: {$request->term_id}");
            return response()->json([
                'success' => true,
                'price' => $price,
                'formatted_price' => number_format($price, 0, '.', ',')
            ]);
        } catch (\Exception $e) {
            Log::error('Error in priceForSelection: ' . $e->getMessage(), [
                'car_id' => $request->car_id,
                'term_id' => $request->term_id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب السعر',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
