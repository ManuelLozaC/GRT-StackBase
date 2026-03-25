import { authStore } from '@/core/auth/authStore';
import { coreRoutes } from '@/core/router/core-routes';
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import { moduleRoutes } from '@/modules';
import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
    history: createWebHistory(),
<<<<<<< HEAD
    routes: [...coreRoutes, ...moduleRoutes]
});

router.beforeEach(async (to) => {
    await authStore.initialize();
=======
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
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3

    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);

    if (requiresAuth && !authStore.isAuthenticated.value) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath
            }
        };
    }

    if (['login', 'register', 'forgotPassword', 'resetPassword'].includes(to.name) && authStore.isAuthenticated.value) {
        return {
            name: 'dashboard'
        };
    }

    const requiredPermission = to.meta?.permissionKey;

    if (requiredPermission && !authStore.hasPermission(requiredPermission)) {
        return {
            name: 'accessDenied'
        };
    }

    if (!requiresAuth) {
        return true;
    }

    await moduleCatalog.loadModules();

    if (to.meta?.moduleKey && !moduleCatalog.isModuleEnabled(to.meta.moduleKey)) {
        return {
            name: 'system-modules'
        };
    }

    return true;
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
