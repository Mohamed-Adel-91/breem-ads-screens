<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScreenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'List screens endpoint not implemented yet.'], 501);
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Create screen form endpoint not implemented yet.'], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Store screen endpoint not implemented yet.'], 501);
    }

    public function show(int $screen): JsonResponse
    {
        return response()->json([
            'message' => 'Show screen endpoint not implemented yet.',
            'screen' => $screen,
        ], 501);
    }

    public function edit(int $screen): JsonResponse
    {
        return response()->json([
            'message' => 'Edit screen form endpoint not implemented yet.',
            'screen' => $screen,
        ], 501);
    }

    public function update(Request $request, int $screen): JsonResponse
    {
        return response()->json([
            'message' => 'Update screen endpoint not implemented yet.',
            'screen' => $screen,
        ], 501);
    }

    public function destroy(int $screen): JsonResponse
    {
        return response()->json([
            'message' => 'Delete screen endpoint not implemented yet.',
            'screen' => $screen,
        ], 501);
    }
}
