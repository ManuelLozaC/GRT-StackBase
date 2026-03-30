import AppLayout from '@/layout/AppLayout.vue';

export const coreRoutes = [
    {
        path: '/',
        name: 'app-shell',
        meta: {
            requiresAuth: true
        },
        component: AppLayout,
        children: [
            {
                path: '/',
                name: 'dashboard',
                component: () => import('@/views/Dashboard.vue')
            },
            {
                path: '/pages/empty',
                name: 'empty',
                component: () => import('@/views/pages/Empty.vue')
            },
            {
                path: '/platform/data-engine',
                name: 'platform-data-engine',
                meta: {
                    permissionKey: 'data-engine.access'
                },
                component: () => import('@/views/pages/PlatformDataEngine.vue')
            },
            {
                path: '/start/documentation',
                name: 'documentation',
                meta: {
                    permissionKey: 'technical.docs.view'
                },
                component: () => import('@/views/pages/Documentation.vue')
            },
            {
                path: '/account/preferences',
                name: 'my-preferences',
                component: () => import('@/views/pages/MyPreferences.vue')
            },
            {
                path: '/account/api-tokens',
                name: 'api-tokens',
                meta: {
                    permissionKey: 'api-tokens.manage'
                },
                component: () => import('@/views/pages/ApiTokens.vue')
            },
            {
                path: '/admin/operations',
                name: 'operations-overview',
                meta: {
                    permissionKey: 'operations.view'
                },
                component: () => import('@/views/pages/OperationsOverview.vue')
            },
            {
                path: '/admin/metrics',
                name: 'usage-metrics',
                meta: {
                    permissionKey: 'metrics.view'
                },
                component: () => import('@/views/pages/UsageMetrics.vue')
            },
            {
                path: '/admin/modules',
                name: 'system-modules',
                meta: {
                    permissionKey: 'modules.view'
                },
                component: () => import('@/views/pages/SystemModules.vue')
            },
            {
                path: '/admin/webhooks',
                name: 'system-webhooks',
                meta: {
                    permissionKey: 'integrations.view'
                },
                component: () => import('@/views/pages/SystemWebhooks.vue')
            },
            {
                path: '/admin/settings',
                name: 'system-settings',
                meta: {
                    permissionKey: 'settings.view'
                },
                component: () => import('@/views/pages/SystemSettings.vue')
            },
            {
                path: '/admin/users',
                name: 'user-administration',
                meta: {
                    permissionKey: 'users.view'
                },
                component: () => import('@/views/pages/UserAdministration.vue')
            },
            {
                path: '/admin/roles',
                name: 'role-administration',
                meta: {
                    permissionKey: 'roles.view'
                },
                component: () => import('@/views/pages/RolePermissionAdministration.vue')
            },
            {
                path: '/admin/security',
                name: 'security-logs',
                meta: {
                    permissionKey: 'security.logs.view'
                },
                component: () => import('@/views/pages/SecurityLogs.vue')
            },
            {
                path: '/admin/errors',
                name: 'error-logs',
                meta: {
                    permissionKey: 'error-logs.view'
                },
                component: () => import('@/views/pages/ErrorLogs.vue')
            }
        ]
    },
    {
        path: '/landing',
        name: 'landing',
        meta: {
            requiresAuth: false
        },
        component: () => import('@/views/pages/Landing.vue')
    },
    {
        path: '/pages/notfound',
        name: 'notfound',
        meta: {
            requiresAuth: false
        },
        component: () => import('@/views/pages/NotFound.vue')
    },
    {
        path: '/auth/login',
        name: 'login',
        meta: {
            requiresAuth: false
        },
        component: () => import('@/views/pages/auth/Login.vue')
    },
    {
        path: '/auth/register',
        name: 'register',
        meta: {
            requiresAuth: false
        },
        component: () => import('@/views/pages/auth/Register.vue')
    },
    {
        path: '/auth/forgot-password',
        name: 'forgotPassword',
        meta: {
            requiresAuth: false
        },
        component: () => import('@/views/pages/auth/ForgotPassword.vue')
    },
    {
        path: '/auth/reset-password',
        name: 'resetPassword',
        meta: {
            requiresAuth: false
        },
        component: () => import('@/views/pages/auth/ResetPassword.vue')
    },
    {
        path: '/auth/access',
        name: 'accessDenied',
        meta: {
            requiresAuth: false
        },
        component: () => import('@/views/pages/auth/Access.vue')
    },
    {
        path: '/auth/error',
        name: 'error',
        meta: {
            requiresAuth: false
        },
        component: () => import('@/views/pages/auth/Error.vue')
    }
];
