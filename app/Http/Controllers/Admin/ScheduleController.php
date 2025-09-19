<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(int $ad): JsonResponse
    {
        return response()->json([
            'message' => 'List ad schedules endpoint not implemented yet.',
            'ad' => $ad,
        ], 501);
    }

    public function store(Request $request, int $ad): JsonResponse
    {
        return response()->json([
            'message' => 'Create ad schedule endpoint not implemented yet.',
            'ad' => $ad,
        ], 501);
    }

    public function update(Request $request, int $ad, int $schedule): JsonResponse
    {
        return response()->json([
            'message' => 'Update ad schedule endpoint not implemented yet.',
            'ad' => $ad,
            'schedule' => $schedule,
        ], 501);
    }

    public function destroy(int $ad, int $schedule): JsonResponse
    {
        return response()->json([
            'message' => 'Delete ad schedule endpoint not implemented yet.',
            'ad' => $ad,
            'schedule' => $schedule,
        ], 501);
    }
}
