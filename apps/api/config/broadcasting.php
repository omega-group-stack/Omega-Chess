<?php

return [
    'default' => env('BROADCAST_CONNECTION', 'null'),
    'connections' => [
        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY', 'omega-local'),
            'secret' => env('REVERB_APP_SECRET', 'omega-local-secret'),
            'app_id' => env('REVERB_APP_ID', 'omega-local'),
            'options' => [
                'host' => env('REVERB_HOST', '127.0.0.1'),
                'port' => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
            'client_options' => [],
        ],
        'null' => ['driver' => 'null'],
    ],
];
