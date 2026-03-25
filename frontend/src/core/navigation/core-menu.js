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
            }
        ]
    }
];
