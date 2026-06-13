<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | Supported: "file", "array", "redis", "memcached", "null"
    |
    */
    'default' => env('CACHE_STORE', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    */
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path'   => dirname(__DIR__) . '/storage/cache',
        ],

        'array' => [
            'driver'   => 'array',
            'serialize' => false,
        ],

        'redis' => [
            'driver'     => 'redis',
            'connection' => 'cache',
            'host'       => env('REDIS_HOST', '127.0.0.1'),
            'port'       => (int) env('REDIS_PORT', '6379'),
            'password'   => env('REDIS_PASSWORD'),
            'database'   => (int) env('REDIS_CACHE_DB', '1'),
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    */
    'prefix' => env('CACHE_PREFIX', 'docile_cache'),
];
