import { accessStore } from '@/core/auth/accessStore';
import { sessionStore } from '@/core/auth/sessionStore';
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import { settingsStore } from '@/core/settings/settingsStore';
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
    await sessionStore.initialize();

    if (sessionStore.isAuthenticated.value) {
        await settingsStore.initialize();
        await moduleCatalog.loadModules();
        ensureModuleRoutesRegistered();

        if (to.matched.length === 0) {
            return to.fullPath;
        }
    }

    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);

    if (requiresAuth && !sessionStore.isAuthenticated.value) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath
            }
        };
    }

    if (['login', 'register', 'forgotPassword', 'resetPassword'].includes(to.name) && sessionStore.isAuthenticated.value) {
        return {
            name: 'dashboard'
        };
    }

    const requiredPermission = to.meta?.permissionKey;

    if (requiredPermission && !accessStore.hasPermission(requiredPermission)) {
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
