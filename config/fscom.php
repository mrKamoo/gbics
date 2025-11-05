<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FS.com Scraper Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for scraping FS.com product catalog
    |
    */

    'base_url' => env('FSCOM_BASE_URL', 'https://www.fs.com'),

    'enabled' => env('FSCOM_SYNC_ENABLED', true),

    'schedule' => env('FSCOM_SYNC_SCHEDULE', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Request Settings
    |--------------------------------------------------------------------------
    */

    'delay_between_requests' => env('FSCOM_DELAY_MS', 2000), // milliseconds

    'max_retries' => env('FSCOM_MAX_RETRIES', 3),

    'timeout' => env('FSCOM_TIMEOUT', 45), // seconds

    /*
    |--------------------------------------------------------------------------
    | Category URLs
    |--------------------------------------------------------------------------
    */

    'categories' => [
        'gbic' => [
            'url' => '/fr/c/optical-transceivers-9',
            'enabled' => true,
        ],
        'patch_cord' => [
            'url' => '/fr/c/fiber-optic-cables-209',
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Agents (for rotation)
    |--------------------------------------------------------------------------
    */

    'user_agents' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ],

    /*
    |--------------------------------------------------------------------------
    | Proxy Settings (optional)
    |--------------------------------------------------------------------------
    */

    'proxy' => [
        'enabled' => env('FSCOM_PROXY_ENABLED', false),
        'host' => env('FSCOM_PROXY_HOST', null),
        'port' => env('FSCOM_PROXY_PORT', null),
        'username' => env('FSCOM_PROXY_USERNAME', null),
        'password' => env('FSCOM_PROXY_PASSWORD', null),
    ],
];
