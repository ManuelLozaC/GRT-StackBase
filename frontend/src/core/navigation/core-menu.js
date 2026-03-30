export const coreMenu = [
    {
        label: 'Home',
        items: [
            {
                label: 'Dashboard',
                icon: 'pi pi-fw pi-home',
                to: '/'
            }
        ]
    },
    {
        label: 'Platform',
        items: [
            {
                label: 'Data Engine',
                icon: 'pi pi-fw pi-database',
                to: '/platform/data-engine',
                permissionKey: 'data-engine.access'
            },
            {
                label: 'Documentation',
                icon: 'pi pi-fw pi-book',
                to: '/start/documentation',
                permissionKey: 'technical.docs.view'
            }
        ]
    },
    {
        label: 'Administration',
        items: [
            {
                label: 'Operations',
                icon: 'pi pi-fw pi-chart-line',
                to: '/admin/operations',
                permissionKey: 'operations.view'
            },
            {
                label: 'Usage Metrics',
                icon: 'pi pi-fw pi-wave-pulse',
                to: '/admin/metrics',
                permissionKey: 'metrics.view'
            },
            {
                label: 'Modules',
                icon: 'pi pi-fw pi-cog',
                to: '/admin/modules',
                permissionKey: 'modules.view'
            },
            {
                label: 'Webhooks',
                icon: 'pi pi-fw pi-send',
                to: '/admin/webhooks',
                permissionKey: 'integrations.view'
            },
            {
                label: 'System Settings',
                icon: 'pi pi-fw pi-sliders-v',
                to: '/admin/settings',
                permissionKey: 'settings.view'
            },
            {
                label: 'Users',
                icon: 'pi pi-fw pi-users',
                to: '/admin/users',
                permissionKey: 'users.view'
            },
            {
                label: 'Roles & Permissions',
                icon: 'pi pi-fw pi-shield',
                to: '/admin/roles',
                permissionKey: 'roles.view'
            },
            {
                label: 'Security Logs',
                icon: 'pi pi-fw pi-shield',
                to: '/admin/security',
                permissionKey: 'security.logs.view'
            },
            {
                label: 'Error Logs',
                icon: 'pi pi-fw pi-exclamation-circle',
                to: '/admin/errors',
                permissionKey: 'error-logs.view'
            }
        ]
    },
    {
        label: 'Account',
        items: [
            {
                label: 'My Preferences',
                icon: 'pi pi-fw pi-user-edit',
                to: '/account/preferences'
            },
            {
                label: 'API Tokens',
                icon: 'pi pi-fw pi-key',
                to: '/account/api-tokens',
                permissionKey: 'api-tokens.manage'
            }
        ]
    }
];
