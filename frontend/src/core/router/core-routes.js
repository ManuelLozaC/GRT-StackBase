import AppLayout from '@/layout/AppLayout.vue';

export const coreRoutes = [
    {
        path: '/',
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
                component: () => import('@/views/pages/PlatformDataEngine.vue')
            },
            {
                path: '/start/documentation',
                name: 'documentation',
                component: () => import('@/views/pages/Documentation.vue')
            },
            {
                path: '/admin/modules',
                name: 'system-modules',
                meta: {
                    permissionKey: 'modules.manage'
                },
                component: () => import('@/views/pages/SystemModules.vue')
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
