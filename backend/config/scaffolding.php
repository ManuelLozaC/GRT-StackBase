<?php

return [
    'backend_modules_path' => env('STACKBASE_BACKEND_MODULES_PATH', base_path('app/Modules')),
    'frontend_modules_path' => env('STACKBASE_FRONTEND_MODULES_PATH', dirname(base_path()).DIRECTORY_SEPARATOR.'frontend'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'modules'),
    'docs_modules_path' => env('STACKBASE_DOCS_MODULES_PATH', dirname(base_path()).DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'modules'),
];
