<?php

namespace App\Services\Screen;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScreenApiService
{
    public function __construct(
        protected AdSchedulerService $scheduler
    ) {
    }

    /**
     * Handle the initial handshake for a screen.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handshake(array $payload): array
    {
        $screen = Screen::query()
            ->where('code', $payload['code'])
            ->firstOrFail();

        $deviceUid = data_get($payload, 'device.uid');

        if ($deviceUid && $screen->device_uid !== $deviceUid) {
            $screen->device_uid = $deviceUid;
        }

        $screen->status = ScreenStatus::Online;
        $screen->last_heartbeat = now();
        $screen->save();

        $config = $this->buildConfigPayload($screen, $payload);

        return [
            'screen' => $screen->fresh(),
            'config' => $config,
            'token' => $screen->device_uid,
            'meta' => [
                'handshaken_at' => now(),
            ],
        ];
    }

    /**
     * Record the heartbeat for a given screen.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function heartbeat(array $payload): array
    {
        $screen = $this->resolveScreen($payload);

        $screen->forceFill([
            'status' => isset($payload['status'])
                ? ScreenStatus::from($payload['status'])
                : ScreenStatus::Online,
            'last_heartbeat' => now(),
        ])->save();

        $logStatus = ($payload['status'] ?? ScreenStatus::Online->value) === ScreenStatus::Offline->value
            ? ScreenStatus::Offline->value
            : ScreenStatus::Online->value;

        $log = $screen->logs()->create([
            'current_ad_code' => $payload['current_ad_code'] ?? null,
            'status' => $logStatus,
            'reported_at' => isset($payload['reported_at'])
                ? Carbon::parse($payload['reported_at'])
                : now(),
        ]);

        return [
            'screen' => $screen->fresh(),
            'log' => $log,
            'next_heartbeat_at' => now()->addSeconds($this->heartbeatInterval()),
        ];
    }

    /**
     * Build the playlist payload for the provided screen.
     *
     * @return array<string, mixed>
     */
    public function playlist(Screen $screen, ?string $ifNoneMatch = null): array
    {
        $payload = $this->scheduler->forScreen($screen);

        /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $items */
        $items = collect($payload['items'] ?? []);
        $screenModel = $payload['screen'] ?? $screen->fresh();

        $etag = (string) ($payload['etag'] ?? ($screenModel
            ? $this->makePlaylistEtag($screenModel, $items)
            : sha1($items->toJson(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))));
        $unchanged = $ifNoneMatch && hash_equals($etag, $ifNoneMatch);

        return [
            'screen' => $screenModel,
            'items' => $items,
            'etag' => $etag,
            'unchanged' => $unchanged,
            'generated_at' => $payload['generated_at'] ?? now(),
            'expires_at' => $payload['expires_at'] ?? now()->addSeconds($this->playlistTtl()),
        ];
    }

    /**
     * Resolve a screen using the provided identifiers.
     *
     * @param  array<string, mixed>  $payload
     */
    public function resolveScreen(array $payload): Screen
    {
        $identifiers = collect([
            'device_uid' => $payload['device_uid'] ?? null,
            'code' => $payload['code'] ?? null,
        ])->filter();

        if ($identifiers->isEmpty()) {
            throw new ModelNotFoundException(__('Unable to determine the screen for the request.'));
        }

        if ($identifiers->has('device_uid')) {
            $screen = Screen::query()
                ->where('device_uid', $identifiers->get('device_uid'))
                ->first();

            if ($screen) {
                return $screen;
            }
        }

        if ($identifiers->has('code')) {
            return Screen::query()
                ->where('code', $identifiers->get('code'))
                ->firstOrFail();
        }

        throw new ModelNotFoundException(__('Screen not found.'));
    }

    /**
     * Build the configuration payload returned to devices.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function buildConfigPayload(Screen $screen, array $payload): array
    {
        return [
            'heartbeat_interval' => $this->heartbeatInterval(),
            'playlist_ttl' => $this->playlistTtl(),
            'timezone' => data_get($payload, 'meta.timezone', config('app.timezone')),
        ];
    }

    /**
     * Generate an ETag for the playlist payload.
     */
    protected function makePlaylistEtag(Screen $screen, Collection $items): string
    {
        $payload = $items->toJson(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return sha1($screen->id.'|'.$screen->updated_at?->timestamp.'|'.$payload);
    }

    /**
     * Retrieve the heartbeat interval in seconds.
     */
    protected function heartbeatInterval(): int
    {
        return (int) config('services.screens.heartbeat_interval', 60);
    }

    /**
     * Retrieve the playlist TTL in seconds.
     */
    protected function playlistTtl(): int
    {
        return (int) config('services.screens.playlist_ttl', 300);
    }
}
