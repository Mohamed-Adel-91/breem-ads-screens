<?php

namespace App\Traits;
trait ResponseTrait
{

    protected function apiResponse(int $status, ?array $meta = null, $data = null, int $statusCode = 200)
    {
        $response = [
            'status' => $status,
            'meta' => $meta,
            'data' => $data,
        ];

        if ($meta === null) {
            $response['meta'] = [];
        }

        return response()->json($response, $statusCode);
    }
}
