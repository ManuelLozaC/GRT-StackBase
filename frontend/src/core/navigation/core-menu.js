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
                to: '/platform/data-engine'
            },
            {
                label: 'Documentation',
                icon: 'pi pi-fw pi-book',
                to: '/start/documentation'
            },
            {
                label: 'My Preferences',
                icon: 'pi pi-fw pi-sliders-h',
                to: '/account/preferences'
            }
        ]
    },
    {
        label: 'Administration',
        items: [
            {
                label: 'Modules',
                icon: 'pi pi-fw pi-cog',
                to: '/admin/modules',
                permissionKey: 'modules.manage'
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
            }
        ]
    }
];
