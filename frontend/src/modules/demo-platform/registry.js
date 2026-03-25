export const demoPlatformRegistry = {
    views: {
        'platform.demo': () => import('@/views/pages/PlatformDemo.vue'),
        'demo.notifications': () => import('@/views/pages/demo/DemoNotifications.vue'),
        'demo.files': () => import('@/views/pages/demo/DemoFiles.vue'),
        'demo.jobs': () => import('@/views/pages/demo/DemoJobs.vue'),
        'demo.audit': () => import('@/views/pages/demo/DemoAudit.vue'),
        'demo.transfers': () => import('@/views/pages/demo/DemoTransfers.vue')
    }
};
