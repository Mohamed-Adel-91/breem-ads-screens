<?php

namespace App\Services\Screen;

use App\Enums\ScreenStatus;
use App\Models\Screen;

class ScreenApiService
{
    public function __construct(
        protected AdSchedulerService $scheduler
    ) {
    }

    /**
     * Build the bootstrap configuration handed to a freshly paired device.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function bootstrapConfig(array $payload = []): array
    {
        return [
            "heartbeat_interval" => $this->heartbeatInterval(),
            "playlist_ttl" => $this->playlistTtl(),
            "timezone" => data_get($payload, "meta.timezone", config("app.timezone")),
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

        // AdSchedulerService owns the ETag: it is the only component that knows
        // what the manifest contains. Recomputing it here once produced a second,
        // subtly different hash for the same payload.
        $etag = (string) ($payload['etag'] ?? '');
        $unchanged = $ifNoneMatch && $etag !== '' && hash_equals($etag, $ifNoneMatch);

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
