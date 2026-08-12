<?php

namespace App\Services\Config;

use App\Models\Screen;
use App\Models\Setting;

class DeviceConfigService
{
    /**
     * Settings a device is entitled to receive.
     *
     * An explicit allow-list, not a filter: the endpoint used to return every
     * row of the settings table, so any future admin- or CMS-only setting would
     * have been published to the whole fleet automatically. Adding a key here is
     * a deliberate decision to expose it to devices.
     *
     * @var array<int, string>
     */
    public const ALLOWED_SETTING_KEYS = [
        'site.phone',
        'site.lang_switch',
    ];

    /**
     * Build the configuration payload for an authenticated screen.
     *
     * @return array<string, mixed>
     */
    public function forScreen(Screen $screen): array
    {
        $config = $this->configPayload();

        return [
            'screen' => $screen,
            'config' => $config,
            'etag' => sha1($screen->id.'|'.json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'generated_at' => now(),
            'expires_at' => now()->addSeconds((int) config('services.screens.config_ttl', 900)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function configPayload(): array
    {
        $settings = Setting::query()
            ->whereIn('key', self::ALLOWED_SETTING_KEYS)
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
