<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
    ],

    'screens' => [
        // Signing secrets are per device and live in screen_device_credentials.
        // There is deliberately no fleet-wide signing key any more: compromising
        // one device must not compromise the others.
        'signature_leeway' => env('SCREENS_SIGNATURE_LEEWAY', 300),
        'pairing_code_ttl' => env('SCREENS_PAIRING_CODE_TTL', 900),

        // How often a device is told to report in.
        'heartbeat_interval' => env('SCREENS_HEARTBEAT_INTERVAL', 60),

        // How long a screen may go without a heartbeat before the server calls
        // it offline. Read through App\Support\ScreenHealth, never directly —
        // that class is the single source of truth and keeps the value coherent
        // with the interval above. Null means "derive from the interval".
        'offline_after' => env('SCREENS_OFFLINE_AFTER'),
        'playlist_ttl' => env('SCREENS_PLAYLIST_TTL', 300),
        'config_ttl' => env('SCREENS_CONFIG_TTL', 900),
    ],

];
