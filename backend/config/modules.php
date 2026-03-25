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
            'frontend' => [
                'navigation' => null,
                'routes' => [],
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
            'frontend' => [
                'navigation' => [
                    'label' => 'Demo',
                ],
                'routes' => [
                    [
                        'path' => '/demo/platform',
                        'name' => 'platform-demo',
                        'view' => 'platform.demo',
                        'meta' => [],
                        'menu' => [
                            'label' => 'Platform Demo',
                            'icon' => 'pi pi-fw pi-play-circle',
                        ],
                    ],
                    [
                        'path' => '/demo/notifications',
                        'name' => 'demo-notifications',
                        'view' => 'demo.notifications',
                        'meta' => [],
                        'menu' => [
                            'label' => 'Notifications Demo',
                            'icon' => 'pi pi-fw pi-bell',
                        ],
                    ],
                    [
                        'path' => '/demo/files',
                        'name' => 'demo-files',
                        'view' => 'demo.files',
                        'meta' => [],
                        'menu' => [
                            'label' => 'Files Demo',
                            'icon' => 'pi pi-fw pi-file',
                        ],
                    ],
                    [
                        'path' => '/demo/jobs',
                        'name' => 'demo-jobs',
                        'view' => 'demo.jobs',
                        'meta' => [],
                        'menu' => [
                            'label' => 'Jobs Demo',
                            'icon' => 'pi pi-fw pi-cog',
                        ],
                    ],
                    [
                        'path' => '/demo/audit',
                        'name' => 'demo-audit',
                        'view' => 'demo.audit',
                        'meta' => [],
                        'menu' => [
                            'label' => 'Audit Demo',
                            'icon' => 'pi pi-fw pi-history',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
