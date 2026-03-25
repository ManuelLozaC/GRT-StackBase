import { authStore } from '@/core/auth/authStore';
import { coreRoutes } from '@/core/router/core-routes';
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import { moduleRoutes } from '@/modules';
import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
    history: createWebHistory(),
    routes: [...coreRoutes, ...moduleRoutes]
});

router.beforeEach(async (to) => {
    await authStore.initialize();

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

export default router;
