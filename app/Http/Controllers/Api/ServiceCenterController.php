<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Helpers\LocationParser;
use App\Models\Brand;
use App\Enums\CityEnum;
use App\Models\ServiceCenter;

class ServiceCenterController extends Controller
{
    public function cities(Brand $brand)
    {
        $cities = $brand->serviceCenters()
            ->pluck('city')
            ->unique()
            ->map(fn($city) => [
                'id' => $city,
                'name' => CityEnum::getDescription($city),
            ])->values();

        return ApiResponse::sendResponse(200, 'cities', $cities);
    }

    public function branches(Brand $brand, $city)
    {
        $branches = $brand->serviceCenters()
            ->where('city', $city)
            ->get()
            ->map(function ($center) {
                [$lat, $lng] = LocationParser::parse($center->location);
                return [
                    'id' => $center->id,
                    'name' => $center->name,
                    'address' => $center->address,
                    'phone' => $center->phone,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'location' => $center->location,
                ];
            });

        return ApiResponse::sendResponse(200, 'branches', $branches);
    }

    public function branch(ServiceCenter $branch)
    {
        [$lat, $lng] = LocationParser::parse($branch->location);
        $data = [
            'id' => $branch->id,
            'name' => $branch->name,
            'address' => $branch->address,
            'phone' => $branch->phone,
            'latitude' => $lat,
            'longitude' => $lng,
            'location' => $branch->location,
        ];

        return ApiResponse::sendResponse(200, 'branch', $data);
    }

}
