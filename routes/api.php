<?php

use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\PlaybackController;
use App\Http\Controllers\Api\ScreenApiController;
use App\Models\Screen;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::prefix('v1')
    ->middleware(['api', 'throttle:api.v1', 'screen.auth'])
    ->group(function (): void {
        Route::bind('screen', function (string $value): Screen {
            return Screen::query()
                ->where('id', $value)
                ->orWhere('code', $value)
                ->firstOrFail();
        });

        Route::post('screens/handshake', [ScreenApiController::class, 'handshake'])
            ->name('api.v1.screens.handshake')
            ->withoutMiddleware(['screen.auth']);

        Route::post('screens/heartbeat', [ScreenApiController::class, 'heartbeat'])
            ->name('api.v1.screens.heartbeat');

        Route::get('screens/{screen}/playlist', [ScreenApiController::class, 'playlist'])
            ->name('api.v1.screens.playlist');

        Route::post('playbacks', [PlaybackController::class, 'store'])
            ->name('api.v1.playbacks.store');

        Route::get('config', ConfigController::class)
            ->name('api.v1.config.show');
    });

