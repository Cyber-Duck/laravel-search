<?php

return [
    'driver' => env('SEARCH_DRIVER', 'es'),

    'host' => env('SEARCH_HOST', ''),

    'index_prefix' => env('SEARCH_INDEX_PREFIX', 'default'),

    'queue' => 0,

    'models_all' => [

    ],

    'fuzziness' => env('SEARCH_FUZZINESS', 'AUTO'),

    'es' => [
        'user' => env('SEARCH_USER', ''),
        'password' => env('SEARCH_PASSWORD'),
        'schema' => env('SEARCH_HOST_SCHEMA'),
        'port' => env('SEARCH_HOST_PORT'),
    ]
];
