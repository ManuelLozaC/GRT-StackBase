export const demoPlatformRegistry = {
    views: {
        'platform.demo': () => import('@/views/pages/PlatformDemo.vue'),
        'demo.ui-showcase': () => import('@/views/pages/demo/DemoUiShowcase.vue'),
        'demo.ui-feedback': () => import('@/views/pages/demo/DemoUiFeedback.vue'),
        'demo.ui-forms': () => import('@/views/pages/demo/DemoUiForms.vue'),
        'demo.ui-data-display': () => import('@/views/pages/demo/DemoUiDataDisplay.vue'),
        'demo.ui-async-patterns': () => import('@/views/pages/demo/DemoUiAsyncPatterns.vue'),
        'demo.ui-layouts': () => import('@/views/pages/demo/DemoUiLayouts.vue'),
        'demo.ui-typography-content': () => import('@/views/pages/demo/DemoUiTypographyContent.vue'),
        'demo.ui-advanced-inputs': () => import('@/views/pages/demo/DemoUiAdvancedInputs.vue'),
        'demo.ui-screen-recipes': () => import('@/views/pages/demo/DemoUiScreenRecipes.vue'),
        'demo.notifications': () => import('@/views/pages/demo/DemoNotifications.vue'),
        'demo.files': () => import('@/views/pages/demo/DemoFiles.vue'),
        'demo.jobs': () => import('@/views/pages/demo/DemoJobs.vue'),
        'demo.audit': () => import('@/views/pages/demo/DemoAudit.vue'),
        'demo.transfers': () => import('@/views/pages/demo/DemoTransfers.vue')
    }
};
