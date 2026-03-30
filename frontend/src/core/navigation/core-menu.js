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
                permissionKey: 'security.manage'
            },
            {
                label: 'Usage Metrics',
                icon: 'pi pi-fw pi-wave-pulse',
                to: '/admin/metrics',
                permissionKey: 'security.manage'
            },
            {
                label: 'Modules',
                icon: 'pi pi-fw pi-cog',
                to: '/admin/modules',
                permissionKey: 'modules.manage'
            },
            {
                label: 'Webhooks',
                icon: 'pi pi-fw pi-send',
                to: '/admin/webhooks',
                permissionKey: 'integrations.manage'
            },
            {
                label: 'System Settings',
                icon: 'pi pi-fw pi-sliders-v',
                to: '/admin/settings',
                permissionKey: 'settings.manage'
            },
            {
                label: 'Users',
                icon: 'pi pi-fw pi-users',
                to: '/admin/users',
                permissionKey: 'users.manage_roles'
            },
            {
                label: 'Roles & Permissions',
                icon: 'pi pi-fw pi-shield',
                to: '/admin/roles',
                permissionKey: 'roles.manage'
            },
            {
                label: 'Security Logs',
                icon: 'pi pi-fw pi-shield',
                to: '/admin/security',
                permissionKey: 'security.manage'
            },
            {
                label: 'Error Logs',
                icon: 'pi pi-fw pi-exclamation-circle',
                to: '/admin/errors',
                permissionKey: 'security.manage'
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
