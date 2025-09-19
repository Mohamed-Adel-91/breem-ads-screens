<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Logs listing endpoint not implemented yet.'], 501);
    }

    public function download(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Logs download endpoint not implemented yet.'], 501);
    }
}
