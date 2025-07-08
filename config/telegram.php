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
    'cache_ttl' => env('TELEGRAM_CACHE_TTL', 300), // 5 minutes
    'timeout' => env('TELEGRAM_TIMEOUT', 30),
];