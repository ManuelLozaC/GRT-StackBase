import AppLayout from '@/layout/AppLayout.vue';

export const installedModules = [];

export const moduleRoutes = [
    {
        path: '/',
        meta: {
            requiresAuth: true
        },
        component: AppLayout,
        children: [
            {
                path: '/demo/platform',
                name: 'platform-demo',
                meta: {
                    moduleKey: 'demo-platform'
                },
                component: () => import('@/views/pages/PlatformDemo.vue')
            },
            {
                path: '/demo/notifications',
                name: 'demo-notifications',
                meta: {
                    moduleKey: 'demo-platform'
                },
                component: () => import('@/views/pages/demo/DemoNotifications.vue')
            },
            {
                path: '/demo/files',
                name: 'demo-files',
                meta: {
                    moduleKey: 'demo-platform'
                },
                component: () => import('@/views/pages/demo/DemoFiles.vue')
            },
            {
                path: '/demo/jobs',
                name: 'demo-jobs',
                meta: {
                    moduleKey: 'demo-platform'
                },
                component: () => import('@/views/pages/demo/DemoJobs.vue')
            },
            {
                path: '/demo/audit',
                name: 'demo-audit',
                meta: {
                    moduleKey: 'demo-platform'
                },
                component: () => import('@/views/pages/demo/DemoAudit.vue')
            }
        ]
    }
];

export const moduleMenu = [
    {
        label: 'Demo',
        moduleKey: 'demo-platform',
        items: [
            {
                label: 'Platform Demo',
                icon: 'pi pi-fw pi-play-circle',
                to: '/demo/platform',
                moduleKey: 'demo-platform'
            },
            {
                label: 'Notifications Demo',
                icon: 'pi pi-fw pi-bell',
                to: '/demo/notifications',
                moduleKey: 'demo-platform'
            },
            {
                label: 'Files Demo',
                icon: 'pi pi-fw pi-file',
                to: '/demo/files',
                moduleKey: 'demo-platform'
            },
            {
                label: 'Jobs Demo',
                icon: 'pi pi-fw pi-cog',
                to: '/demo/jobs',
                moduleKey: 'demo-platform'
            },
            {
                label: 'Audit Demo',
                icon: 'pi pi-fw pi-history',
                to: '/demo/audit',
                moduleKey: 'demo-platform'
            }
        ]
    }
];
