import AppLayout from '@/layout/AppLayout.vue';
import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            component: AppLayout,
            children: [
                {
                    path: '/',
                    name: 'dashboard',
                    component: () => import('@/views/Dashboard.vue'),
                    meta: {
                        requiereAuth: true
                    }
                },
                {
                    path: '/pages/empty',
                    name: 'empty',
                    component: () => import('@/views/pages/Empty.vue'),
                    meta: {
                        requiereAuth: true
                    }
                },
                {
                    path: '/usuarios',
                    name: 'usuarios',
                    component: () => import('@/views/pages/Crud.vue'),
                    meta: {
                        requiereAuth: true
                    }
                }
            ]
        },
        {
            path: '/pages/notfound',
            name: 'notfound',
            component: () => import('@/views/pages/NotFound.vue')
        },

        {
            path: '/auth/login',
            name: 'login',
            component: () => import('@/views/pages/auth/Login.vue')
        },
        {
            path: '/auth/access',
            name: 'accessDenied',
            component: () => import('@/views/pages/auth/Access.vue')
        },
        {
            path: '/auth/error',
            name: 'error',
            component: () => import('@/views/pages/auth/Error.vue')
        }
    ]
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (auth.token && !auth.usuario) {
        try {
            await auth.cargarSesion();
        } catch (error) {
            return { name: 'login' };
        }
    }

    if (to.meta?.requiereAuth && !auth.estaAutenticado) {
        return { name: 'login' };
    }

    if (to.name === 'login' && auth.estaAutenticado) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
