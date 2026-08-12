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

    /*
     * Slack delivery for operational alerts, via the installed
     * laravel/slack-notification-channel package.
     *
     * Two routes, both optional and both unset by default:
     *
     *   - `webhook_url` — an Incoming Webhook. The package's router sends any
     *     `slack` route that looks like a URL through SlackWebhookChannel.
     *   - `notifications.*` — a bot token plus default channel, for the Slack Web
     *     API. These are the package's own documented config keys. The operational
     *     jobs already read them, but they were **missing from this file**, so that
     *     branch could never be true and the token/channel path was permanently
     *     dead. Declaring them here makes the absence honest configuration rather
     *     than a phantom code path.
     *
     * With none of these set, Slack is simply not a channel; mail and the log
     * channel still run.
     */
    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),

        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
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
