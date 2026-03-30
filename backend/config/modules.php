<?php

return [
    'installed' => [
        'core-platform' => [
            'name' => 'Core Platform',
            'description' => 'Servicios transversales del sistema base.',
            'version' => '0.1.0',
            'enabled' => true,
            'is_demo' => false,
            'protected' => true,
            'provider' => App\Modules\CorePlatform\CorePlatformServiceProvider::class,
            'dependencies' => [],
            'permissions' => [
                'modules.manage',
                'settings.manage',
                'integrations.manage',
                'users.manage_roles',
                'users.impersonate',
                'roles.manage',
                'tenancy.manage',
                'security.manage',
                'operations.view',
                'metrics.view',
                'security.logs.view',
                'error-logs.view',
            ],
            'settings' => [
                [
                    'key' => 'support_email',
                    'label' => 'Email de soporte',
                    'type' => 'text',
                    'default' => 'soporte@stackbase.local',
                    'help' => 'Contacto base del core para mensajes operativos y soporte.',
                ],
            ],
            'features' => [
                'auth',
                'tenancy',
                'files',
                'jobs',
                'audit',
                'notifications',
            ],
            'jobs' => [],
            'webhooks' => [
                [
                    'key' => 'module.status.updated',
                    'label' => 'Cambio de estado de modulo',
                    'description' => 'Se emite cuando un administrador habilita o deshabilita un modulo.',
                ],
            ],
            'dashboards' => [],
            'seeders' => [
                Database\Seeders\RolePermissionSeeder::class,
            ],
            'assets' => [],
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
            'protected' => false,
            'provider' => App\Modules\DemoPlatform\DemoPlatformServiceProvider::class,
            'dependencies' => [
                'core-platform',
            ],
            'permissions' => [
                'demo.access',
            ],
            'settings' => [
                [
                    'key' => 'default_file_ttl_minutes',
                    'label' => 'TTL por defecto para links temporales',
                    'type' => 'number',
                    'default' => 30,
                    'help' => 'Minutos usados por defecto al generar signed URLs demo.',
                ],
                [
                    'key' => 'notification_default_level',
                    'label' => 'Nivel por defecto de notificaciones demo',
                    'type' => 'select',
                    'default' => 'info',
                    'help' => 'Nivel usado si el usuario no envia uno al crear una notificacion demo.',
                    'options' => [
                        ['label' => 'Info', 'value' => 'info'],
                        ['label' => 'Success', 'value' => 'success'],
                        ['label' => 'Warning', 'value' => 'warning'],
                        ['label' => 'Error', 'value' => 'danger'],
                    ],
                ],
            ],
            'features' => [
                'demo.ui-showcase',
                'demo.ui-feedback',
                'demo.ui-forms',
                'demo.ui-data-display',
                'demo.ui-async-patterns',
                'demo.ui-layouts',
                'demo.ui-typography-content',
                'demo.ui-advanced-inputs',
                'demo.ui-screen-recipes',
                'demo.notifications',
                'demo.files',
                'demo.jobs',
                'demo.audit',
                'demo.transfers',
            ],
            'jobs' => [
                App\Jobs\Demo\ProcessDemoJobRun::class,
                App\Jobs\DataEngine\ProcessDataExportRun::class,
            ],
            'webhooks' => [
                [
                    'key' => 'demo.notification.created',
                    'label' => 'Notificacion demo creada',
                    'description' => 'Se emite cuando el Demo Module genera una notificacion interna o multicanal.',
                ],
                [
                    'key' => 'demo.file.uploaded',
                    'label' => 'Archivo demo cargado',
                    'description' => 'Se emite al registrar una nueva carga de archivo dentro del modulo de demo.',
                ],
            ],
            'dashboards' => [],
            'seeders' => [],
            'assets' => [],
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
                        'path' => '/demo/ui-showcase',
                        'name' => 'demo-ui-showcase',
                        'view' => 'demo.ui-showcase',
                        'meta' => [],
                        'menu' => [
                            'label' => 'UI Showcase',
                            'icon' => 'pi pi-fw pi-palette',
                        ],
                    ],
                    [
                        'path' => '/demo/ui-feedback',
                        'name' => 'demo-ui-feedback',
                        'view' => 'demo.ui-feedback',
                        'meta' => [],
                        'menu' => [
                            'label' => 'UI Feedback',
                            'icon' => 'pi pi-fw pi-megaphone',
                        ],
                    ],
                    [
                        'path' => '/demo/ui-forms',
                        'name' => 'demo-ui-forms',
                        'view' => 'demo.ui-forms',
                        'meta' => [],
                        'menu' => [
                            'label' => 'UI Forms',
                            'icon' => 'pi pi-fw pi-file-edit',
                        ],
                    ],
                    [
                        'path' => '/demo/ui-data-display',
                        'name' => 'demo-ui-data-display',
                        'view' => 'demo.ui-data-display',
                        'meta' => [],
                        'menu' => [
                            'label' => 'UI Data Display',
                            'icon' => 'pi pi-fw pi-table',
                        ],
                    ],
                    [
                        'path' => '/demo/ui-async-patterns',
                        'name' => 'demo-ui-async-patterns',
                        'view' => 'demo.ui-async-patterns',
                        'meta' => [],
                        'menu' => [
                            'label' => 'UI Async Patterns',
                            'icon' => 'pi pi-fw pi-sync',
                        ],
                    ],
                    [
                        'path' => '/demo/ui-layouts',
                        'name' => 'demo-ui-layouts',
                        'view' => 'demo.ui-layouts',
                        'meta' => [],
                        'menu' => [
                            'label' => 'UI Layouts',
                            'icon' => 'pi pi-fw pi-th-large',
                        ],
                    ],
                    [
                        'path' => '/demo/ui-typography-content',
                        'name' => 'demo-ui-typography-content',
                        'view' => 'demo.ui-typography-content',
                        'meta' => [],
                        'menu' => [
                            'label' => 'UI Typography',
                            'icon' => 'pi pi-fw pi-align-left',
                        ],
                    ],
                    [
                        'path' => '/demo/ui-advanced-inputs',
                        'name' => 'demo-ui-advanced-inputs',
                        'view' => 'demo.ui-advanced-inputs',
                        'meta' => [],
                        'menu' => [
                            'label' => 'UI Advanced Inputs',
                            'icon' => 'pi pi-fw pi-sliders-h',
                        ],
                    ],
                    [
                        'path' => '/demo/ui-screen-recipes',
                        'name' => 'demo-ui-screen-recipes',
                        'view' => 'demo.ui-screen-recipes',
                        'meta' => [],
                        'menu' => [
                            'label' => 'UI Screen Recipes',
                            'icon' => 'pi pi-fw pi-clone',
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
                    [
                        'path' => '/demo/transfers',
                        'name' => 'demo-transfers',
                        'view' => 'demo.transfers',
                        'meta' => [],
                        'menu' => [
                            'label' => 'Transfers Demo',
                            'icon' => 'pi pi-fw pi-download',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
