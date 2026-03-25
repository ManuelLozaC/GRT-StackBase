import { authStore } from '@/core/auth/authStore';
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import { coreRoutes } from '@/core/router/core-routes';
import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
    history: createWebHistory(),
    routes: [...coreRoutes]
});

function ensureModuleRoutesRegistered() {
    const routeNames = [];

    moduleCatalog.routeRecords.value.forEach((routeRecord) => {
        if (!router.hasRoute(routeRecord.name)) {
            router.addRoute('app-shell', routeRecord);
        }

        routeNames.push(routeRecord.name);
    });

    moduleCatalog.markRoutesRegistered(routeNames);
}

router.beforeEach(async (to) => {
    await authStore.initialize();

    if (authStore.isAuthenticated.value) {
        await moduleCatalog.loadModules();
        ensureModuleRoutesRegistered();

        if (to.matched.length === 0) {
            return to.fullPath;
        }
    }

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

    if (to.meta?.moduleKey && !moduleCatalog.isModuleEnabled(to.meta.moduleKey)) {
        return {
            name: 'system-modules'
        };
    }

    return true;
});

export default router;
