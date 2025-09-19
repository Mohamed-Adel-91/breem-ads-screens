<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'List places endpoint not implemented yet.'], 501);
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Create place form endpoint not implemented yet.'], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Store place endpoint not implemented yet.'], 501);
    }

    public function show(int $place): JsonResponse
    {
        return response()->json([
            'message' => 'Show place endpoint not implemented yet.',
            'place' => $place,
        ], 501);
    }

    public function edit(int $place): JsonResponse
    {
        return response()->json([
            'message' => 'Edit place form endpoint not implemented yet.',
            'place' => $place,
        ], 501);
    }

    public function update(Request $request, int $place): JsonResponse
    {
        return response()->json([
            'message' => 'Update place endpoint not implemented yet.',
            'place' => $place,
        ], 501);
    }

    public function destroy(int $place): JsonResponse
    {
        return response()->json([
            'message' => 'Delete place endpoint not implemented yet.',
            'place' => $place,
        ], 501);
    }
}
