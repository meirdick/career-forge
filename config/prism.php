<?php

return [
    'request_timeout' => env('PRISM_REQUEST_TIMEOUT', 60),

    'providers' => [
        'workers-ai' => [
            'api_key' => env('CLOUDFLARE_AI_API_KEY', ''),
            'url' => env('WORKERS_AI_URL'),
        ],
    ],
];
