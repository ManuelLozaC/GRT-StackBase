export const demoPlatformManifest = {
    key: 'demo-platform',
    name: 'Demo Platform',
    description: 'Modulo de demostracion para validar capacidades genericas del core.',
    isDemo: true,
    navigation: {
        label: 'Demo'
    },
    routes: [
        {
            path: '/demo/platform',
            name: 'platform-demo',
            component: () => import('@/views/pages/PlatformDemo.vue'),
            menu: {
                label: 'Platform Demo',
                icon: 'pi pi-fw pi-play-circle'
            }
        },
        {
            path: '/demo/notifications',
            name: 'demo-notifications',
            component: () => import('@/views/pages/demo/DemoNotifications.vue'),
            menu: {
                label: 'Notifications Demo',
                icon: 'pi pi-fw pi-bell'
            }
        },
        {
            path: '/demo/files',
            name: 'demo-files',
            component: () => import('@/views/pages/demo/DemoFiles.vue'),
            menu: {
                label: 'Files Demo',
                icon: 'pi pi-fw pi-file'
            }
        },
        {
            path: '/demo/jobs',
            name: 'demo-jobs',
            component: () => import('@/views/pages/demo/DemoJobs.vue'),
            menu: {
                label: 'Jobs Demo',
                icon: 'pi pi-fw pi-cog'
            }
        },
        {
            path: '/demo/audit',
            name: 'demo-audit',
            component: () => import('@/views/pages/demo/DemoAudit.vue'),
            menu: {
                label: 'Audit Demo',
                icon: 'pi pi-fw pi-history'
            }
        }
    ]
};
