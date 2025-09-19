<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Monitoring overview endpoint not implemented yet.'], 501);
    }

    public function showScreen(int $screen): JsonResponse
    {
        return response()->json([
            'message' => 'Screen monitoring endpoint not implemented yet.',
            'screen' => $screen,
        ], 501);
    }

    public function acknowledgeAlert(Request $request, int $screen): JsonResponse
    {
        return response()->json([
            'message' => 'Acknowledge monitoring alert endpoint not implemented yet.',
            'screen' => $screen,
        ], 501);
    }
}
