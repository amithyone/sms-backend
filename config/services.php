<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS, and others. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Services Configuration
    |--------------------------------------------------------------------------
    */

    'sms' => [
        '5sim' => [
            // Use handler API by default per latest docs
            'base_url' => env('5SIM_BASE_URL', 'http://api1.5sim.net/stubs/handler_api.php'),
            'api_key' => env('5SIM_API_KEY'),
        ],
        'dassy' => [
            'base_url' => env('DASSY_BASE_URL', 'https://daisysms.com/stubs/handler_api.php'),
            'api_key' => env('DASSY_API_KEY'),
        ],
        'tiger_sms' => [
            'base_url' => env('TIGER_SMS_BASE_URL', 'https://api.tiger-sms.com/stubs/handler_api.php'),
            'api_key' => env('TIGER_SMS_API_KEY'),
        ],
    ],

    // Global FX and markup for SMS prices
    'sms_fx' => [
        'ngn_per_usd' => env('SMS_FX_NGN_PER_USD', 1600),
        // How many USD per 1 RUB (RUB → USD). Example: 0.011 means 1 RUB = $0.011
        'usd_per_rub' => env('SMS_FX_USD_PER_RUB', 0.011),
        'providers' => [
            // 'dassy' => 1600,
        ],
    ],
    'sms_markup' => [
        'percent' => env('SMS_MARKUP_PERCENT', 0),
        'providers' => [
            // 'dassy' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | VTU Services Configuration
    |--------------------------------------------------------------------------
    */

    'vtu' => [
        'vtu_ng' => [
            'base_url' => env('VTU_NG_BASE_URL', 'https://vtu.ng/wp-json'),
            'username' => env('VTU_NG_USERNAME'),
            'password' => env('VTU_NG_PASSWORD'),
            'pin' => env('VTU_NG_PIN'),
            'token_cache_minutes' => env('VTU_NG_TOKEN_CACHE_MINUTES', 10080),
        ],
        'irecharge' => [
            'base_url' => env('IRECHARGE_BASE_URL', 'https://irecharge.com.ng/pwr_api_sandbox/'),
            'username' => env('IRECHARGE_USERNAME'),
            'password' => env('IRECHARGE_PASSWORD'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Proxy Services Configuration
    |--------------------------------------------------------------------------
    */

    'proxy' => [
        'webshare' => [
            'base_url' => env('WEBSHARE_BASE_URL', 'https://proxy.webshare.io/api/v2'),
            'api_key' => env('WEBSHARE_API_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Services Configuration
    |--------------------------------------------------------------------------
    */

    'payment' => [
        'payvibe' => [
            'base_url' => env('PAYVIBE_BASE_URL', 'https://payvibeapi.six3tech.com/api/v1'),
            'public_key' => env('PAYVIBE_PUBLIC_KEY'),
            'secret_key' => env('PAYVIBE_SECRET_KEY'),
            'product_identifier' => env('PAYVIBE_PRODUCT_IDENTIFIER', 'sms'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    */

    'cors' => [
        'allowed_origins' => array_merge(
            explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:3000')),
            explode(',', env('CORS_PRODUCTION_ORIGINS', 'https://fadsms.com,https://www.fadsms.com'))
        ),
    ],
];
