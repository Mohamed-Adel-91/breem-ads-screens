<?php

namespace App\Http\Middleware;

use App\Models\Screen;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureScreenAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('api.v1.screens.handshake')) {
            return $next($request);
        }

        if ($token = $request->bearerToken()) {
            $request->attributes->set('screen_bearer_token', $token);
        }

        if ($request->attributes->has('authenticated_screen')) {
            return $next($request);
        }

        if ($uid = $request->headers->get('X-Screen-Uid')) {
            $screen = Screen::query()->where('device_uid', $uid)->first();

            if (! $screen) {
                return $this->unauthorized(__('Unknown screen identifier provided.'));
            }

            $request->attributes->set('authenticated_screen', $screen);

            return $next($request);
        }

        if ($request->route('screen') instanceof Screen) {
            return $next($request);
        }

        if ($request->input('device_uid') || $request->input('code')) {
            return $next($request);
        }

        return $this->unauthorized(__('Device authentication required.'));
    }

    /**
     * Create an unauthorized JSON response.
     */
    protected function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }
}
