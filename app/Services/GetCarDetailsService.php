<?php
namespace App\Services;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\CarTerm;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
class GetCarDetailsService
{
    public function modelsByBrand(Brand $brand): Collection
    {
        return CarModel::where('brand_id', $brand->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }
    public function termsByCar(CarModel $car): Collection
    {
        return CarTerm::where('car_model_id', $car->id)
            ->select('id', 'term_name', 'price')
            ->orderBy('term_name')
            ->get();
    }
    public function priceForSelection(int $carId, ?int $termId = null, ?int $programId = null, ?int $insuranceId = null, ?float $carPrice = null): ?float
    {
        $car = CarModel::find($carId);
        if (!$car) {
            Log::error("Car not found with ID: {$carId}");
            return null;
        }
        $price = null;
        if ($termId) {
            $term = CarTerm::where('car_model_id', $carId)
                ->where('id', $termId)
                ->select('id', 'price')
                ->first();
            if ($term && $term->price !== null) {
                $price = (float) $term->price;
                Log::info("Price found from term: {$price}");
            } else {
                Log::warning("Term not found or price is null. Car ID: {$carId}, Term ID: {$termId}");
            }
        }
        if ($price === null && isset($car->start_price)) {
            $price = (float) $car->start_price;
            Log::info("Using car start_price: {$price}");
        }
        if ($price === null) {
            Log::error("No price found for car ID: {$carId}, term ID: {$termId}");
        }
        return $price;
    }
    public function validateCarAndTerm(int $carId, ?int $termId = null): array
    {
        $car = CarModel::find($carId);
        if (!$car) {
            return ['valid' => false, 'message' => 'Car not found'];
        }
        if ($termId) {
            $term = CarTerm::where('car_model_id', $carId)
                ->where('id', $termId)
                ->first();
            if (!$term) {
                return ['valid' => false, 'message' => 'Term not found for this car'];
            }
        }
        return ['valid' => true, 'message' => 'Valid'];
    }
}
