<?php

namespace App\Services\Config;

use App\Models\Setting;
use App\Services\Screen\ScreenApiService;

class DeviceConfigService
{
    public function __construct(
        protected ScreenApiService $screenService
    ) {
    }

    /**
     * Build the device configuration payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function forRequest(array $payload): array
    {
        $screen = null;

        try {
            $screen = $this->screenService->resolveScreen($payload);
        } catch (\Throwable) {
            // Screens may request global config before pairing; ignore failures.
        }

        $config = $this->configPayload();

        $etag = sha1(json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        if ($screen) {
            $etag = sha1($screen->id.'|'.$etag);
        }

        return [
            'screen' => $screen,
            'config' => $config,
            'etag' => $etag,
            'generated_at' => now(),
            'expires_at' => now()->addSeconds((int) config('services.screens.config_ttl', 900)),
        ];
    }

    /**
     * Retrieve the persisted settings payload.
     */
    protected function configPayload(): array
    {
        $settings = Setting::query()
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn (Setting $setting) => [$setting->key => $setting->value])
            ->toArray();

        return [
            'heartbeat_interval' => (int) config('services.screens.heartbeat_interval', 60),
            'playlist_ttl' => (int) config('services.screens.playlist_ttl', 300),
            'refresh_interval' => (int) config('services.screens.config_ttl', 900),
            'settings' => $settings,
        ];
    }
}
