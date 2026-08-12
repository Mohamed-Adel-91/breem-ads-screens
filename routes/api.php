<?php

use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\PlaybackController;
use App\Http\Controllers\Api\ScreenApiController;
use App\Models\Screen;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Device API (v1)
|--------------------------------------------------------------------------
|
| Registered by bootstrap/app.php under the `api` prefix, giving /api/v1/*.
|
| Every endpoint except the handshake requires a per-device bearer token plus a
| signed, timestamped, nonced request — see EnsureScreenAuthentication and
| docs/ai/digital-signage.md. The handshake is unauthenticated by definition and
| therefore carries a stricter limiter of its own.
|
*/

// screen.auth runs BEFORE the throttle so the limiter can key on the resolved
// device credential rather than the IP; a whole site behind one NAT address
// would otherwise share a single bucket. Authentication is one indexed lookup
// and fails closed immediately, and the unauthenticated handshake keeps its own
// IP-keyed limiter below.
Route::prefix('v1')
    ->middleware(['api', 'screen.auth', 'throttle:api.v1'])
    ->group(function (): void {
        Route::bind('screen', function (string $value): Screen {
            return Screen::query()
                ->where('id', $value)
                ->orWhere('code', $value)
                ->firstOrFail();
        });

        Route::post('screens/handshake', [ScreenApiController::class, 'handshake'])
            ->name('api.v1.screens.handshake')
            ->withoutMiddleware(['screen.auth', 'throttle:api.v1'])
            ->middleware('throttle:api.v1.handshake');

        Route::post('screens/heartbeat', [ScreenApiController::class, 'heartbeat'])
            ->name('api.v1.screens.heartbeat');

        Route::get('screens/{screen}/playlist', [ScreenApiController::class, 'playlist'])
            ->name('api.v1.screens.playlist');

        Route::post('playbacks', [PlaybackController::class, 'store'])
            ->name('api.v1.playbacks.store');

        Route::get('config', ConfigController::class)
            ->name('api.v1.config.show');
    });
