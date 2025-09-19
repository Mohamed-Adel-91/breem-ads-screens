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
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'screens' => [
        'hmac_secret' => env('SCREENS_HMAC_SECRET'),
        'signature_leeway' => env('SCREENS_SIGNATURE_LEEWAY', 300),
        'heartbeat_interval' => env('SCREENS_HEARTBEAT_INTERVAL', 60),
        'playlist_ttl' => env('SCREENS_PLAYLIST_TTL', 300),
        'config_ttl' => env('SCREENS_CONFIG_TTL', 900),
    ],

];
