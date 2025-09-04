<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponseTrait
{
    /**
     * Response with status code 200.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @return JsonResponse
     */
    public function successResponse(mixed $data = [], string $message = null): JsonResponse
    {
        if (!$message) {
            $message = trans('api.response.success');
        }

        $response = [
            'status' => true,
            'meta' => [
                'message' => $message,
                'errors' => [],
            ],
            'data' => $data,
        ];

        return new JsonResponse($response, Response::HTTP_OK);
    }

    /**
     * Error Response.
     *
     * @param  mixed  $errors
     * @param  string  $message
     * @param  int  $statusCode
     * @return JsonResponse
     */
    public function errorResponse(mixed $errors = [], string $message = null, int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        if (!$message) {
            $message = trans('api.response.error');
        }

        $response = [
            'status' => false,
            'meta' => [
                'message' => $message,
                'errors' => $errors,
            ],
            'data' => [],
        ];

        return new JsonResponse($response, $statusCode);
    }


    /**
     * Validation Error Response.
     *
     * @param  mixed  $errors
     * @param  string  $message
     * @param  int  $statusCode
     * @return JsonResponse
     */
    public function validationErrorResponse(mixed $errors, string $message = null, int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        if (!$message) {
            $message = trans('api.response.validation_error');
        }

        $response = [
            'status' => false,
            'meta' => [
                'message' => $message,
                'errors' => $errors,
            ],
            'data' => [],
        ];

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Unauthenticated Response.
     *
     * @param  string|null  $message
     * @param  int  $statusCode
     * @return JsonResponse
     */
    public function unauthenticatedResponse(?string $message = null, int $statusCode = Response::HTTP_UNAUTHORIZED): JsonResponse
    {
        if (!$message) {
            $message = __('api.unauthenticated');
        }

        return $this->errorResponse([], $message, $statusCode);
    }
}
