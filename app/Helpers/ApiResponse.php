<?php

namespace App\Helpers;

class ApiResponse
{
    static function sendResponse($code = 200, $message = null, $data = null, $errors = [])
    {
        $response = [
            'status'   => $code,
            'message'  => $message,
        ];

        if ($code >= 400) {
            $response['errors'] = $errors;
        } else {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }
}
