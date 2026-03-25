<?php

return [
    'installed' => [
        'core-platform' => [
            'name' => 'Core Platform',
            'description' => 'Servicios transversales del sistema base.',
            'version' => '0.1.0',
            'enabled' => true,
            'is_demo' => false,
            'provider' => App\Modules\CorePlatform\CorePlatformServiceProvider::class,
            'dependencies' => [],
            'permissions' => [],
            'settings' => [],
            'features' => [
                'auth',
                'tenancy',
                'files',
                'jobs',
                'audit',
                'notifications',
            ],
        ],
        'demo-platform' => [
            'name' => 'Demo Platform',
            'description' => 'Modulo de demostracion para probar capacidades genericas de la plataforma.',
            'version' => '0.1.0',
            'enabled' => false,
            'is_demo' => true,
            'provider' => App\Modules\DemoPlatform\DemoPlatformServiceProvider::class,
            'dependencies' => [
                'core-platform',
            ],
            'permissions' => [],
            'settings' => [],
            'features' => [
                'demo.notifications',
                'demo.files',
                'demo.jobs',
                'demo.audit',
            ],
        ],
    ],
];
