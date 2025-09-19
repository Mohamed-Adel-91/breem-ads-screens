<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'List ads endpoint not implemented yet.'], 501);
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Create ad form endpoint not implemented yet.'], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Store ad endpoint not implemented yet.'], 501);
    }

    public function show(int $ad): JsonResponse
    {
        return response()->json([
            'message' => 'Show ad endpoint not implemented yet.',
            'ad' => $ad,
        ], 501);
    }

    public function edit(int $ad): JsonResponse
    {
        return response()->json([
            'message' => 'Edit ad form endpoint not implemented yet.',
            'ad' => $ad,
        ], 501);
    }

    public function update(Request $request, int $ad): JsonResponse
    {
        return response()->json([
            'message' => 'Update ad endpoint not implemented yet.',
            'ad' => $ad,
        ], 501);
    }

    public function destroy(int $ad): JsonResponse
    {
        return response()->json([
            'message' => 'Delete ad endpoint not implemented yet.',
            'ad' => $ad,
        ], 501);
    }
}
