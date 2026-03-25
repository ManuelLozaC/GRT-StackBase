<?php

return [
    'title' => env('OPENAPI_TITLE', 'StackBase API'),
    'version' => env('OPENAPI_VERSION', '1.0.0'),
    'description' => env('OPENAPI_DESCRIPTION', 'Documentacion ejecutable de la API modular de StackBase.'),
    'servers' => [
        [
            'url' => env('APP_URL', 'http://localhost:8080').'/api/v1',
            'description' => 'API principal',
        ],
    ],
];
