<?php

return [
    'default_engine' => env('SEARCH_ENGINE', 'database'),

    'meilisearch' => [
        'host' => env('MEILI_HOST', 'http://search:7700'),
        'master_key' => env('MEILI_MASTER_KEY'),
        'index_prefix' => env('MEILI_INDEX_PREFIX', 'grt_stackbase_'),
        'timeout_seconds' => (int) env('MEILI_TIMEOUT_SECONDS', 5),
    ],
];
