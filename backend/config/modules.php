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
        ],
        'demo-platform' => [
            'name' => 'Demo Platform',
            'description' => 'Modulo de demostracion para probar capacidades genericas de la plataforma.',
            'version' => '0.1.0',
            'enabled' => false,
            'is_demo' => true,
            'provider' => App\Modules\DemoPlatform\DemoPlatformServiceProvider::class,
        ],
    ],
];
