<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram MTProto Configuration
    |--------------------------------------------------------------------------
    |
    | To use MTProto, you need to register an application at:
    | https://my.telegram.org/apps
    | Advantage: Can access any public channel without being admin
    |
    */
    'api_id' => env('TELEGRAM_API_ID'),
    'api_hash' => env('TELEGRAM_API_HASH'),

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    */
    'session_file' => env('TELEGRAM_SESSION_FILE', 'telegram.madeline'),

    /*
    |--------------------------------------------------------------------------
    | General Configuration
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => env('TELEGRAM_CACHE_TTL', 300), // 5 minutes for messages
    'statistics_cache_ttl' => env('TELEGRAM_STATS_CACHE_TTL', 3600), // 1 hour for statistics
    'max_statistics_days' => env('TELEGRAM_MAX_STATS_DAYS', 15), // Max days for statistics
    'timeout' => env('TELEGRAM_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Blocked Channels
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of channel usernames that are blocked from access
    | Example: 'channel1,channel2,channel3'
    |
    */
    'blocked_channels' => array_map('trim', array_filter(explode(',', env('TELEGRAM_BLOCKED_CHANNELS', '')))),
];
