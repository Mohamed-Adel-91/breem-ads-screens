<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Reports index endpoint not implemented yet.'], 501);
    }

    public function generate(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Report generation endpoint not implemented yet.'], 501);
    }

    public function show(int $report): JsonResponse
    {
        return response()->json([
            'message' => 'Report detail endpoint not implemented yet.',
            'report' => $report,
        ], 501);
    }

    public function download(int $report): JsonResponse
    {
        return response()->json([
            'message' => 'Report download endpoint not implemented yet.',
            'report' => $report,
        ], 501);
    }
}
