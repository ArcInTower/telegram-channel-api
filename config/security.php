<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Channel Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security settings for channel access
    |
    */

    'channels' => [
        /*
        |--------------------------------------------------------------------------
        | Whitelist Mode
        |--------------------------------------------------------------------------
        |
        | When enabled, only channels in the whitelist can be accessed
        | Set to true for maximum security
        |
        */
        'whitelist_enabled' => env('CHANNEL_WHITELIST_ENABLED', false),

        /*
        |--------------------------------------------------------------------------
        | Whitelisted Channels
        |--------------------------------------------------------------------------
        |
        | List of allowed channels when whitelist mode is enabled
        | Format: '@channelname' or 'channelname'
        |
        */
        'whitelist' => env('CHANNEL_WHITELIST') ? explode(',', env('CHANNEL_WHITELIST')) : [
            // '@example_channel',
            // '@another_channel',
        ],

        /*
        |--------------------------------------------------------------------------
        | Blocked Channels
        |--------------------------------------------------------------------------
        |
        | Channels that are always blocked regardless of whitelist mode
        |
        */
        'blocklist' => [
            'self',
            'me', 
            'saved',
            'settings',
            'telegram',
            'botfather',
            'bot',
            'service',
            'support',
            'spam',
            'spambot',
            'stickers',
            'gif',
            'wiki',
            'help',
        ],

        /*
        |--------------------------------------------------------------------------
        | Suspicious Patterns
        |--------------------------------------------------------------------------
        |
        | Regex patterns that indicate suspicious channel names
        |
        */
        'suspicious_patterns' => [
            '/^[a-z]{1,3}\d{1,10}$/i',  // Short name with numbers
            '/test\d+/i',                 // test channels
            '/temp/i',                    // temporary channels
            '/^\d{3,}$/',                 // Only numbers
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for security
    |
    */
    'rate_limits' => [
        'per_channel_per_hour' => env('CHANNEL_RATE_LIMIT', 300),
        'global_per_minute' => env('GLOBAL_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Logging
    |--------------------------------------------------------------------------
    |
    | Configure security event logging
    |
    */
    'logging' => [
        'log_suspicious_access' => env('LOG_SUSPICIOUS_ACCESS', true),
        'log_blocked_attempts' => env('LOG_BLOCKED_ATTEMPTS', true),
        'alert_on_patterns' => env('ALERT_ON_PATTERNS', true),
    ],
];