import { computed, ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const isAuthenticated = ref(false);
const initializeMock = vi.fn();
const settingsInitializeMock = vi.fn();
const loadModulesMock = vi.fn();
const markRoutesRegisteredMock = vi.fn();
const hasPermissionMock = vi.fn();
const isModuleEnabledMock = vi.fn();

vi.mock('@/core/auth/sessionStore', () => ({
    sessionStore: {
        state: {
            user: null
        },
        initialize: initializeMock,
        isAuthenticated: computed(() => isAuthenticated.value)
    }
}));

vi.mock('@/core/settings/settingsStore', () => ({
    settingsStore: {
        initialize: settingsInitializeMock
    }
}));

vi.mock('@/core/modules/moduleCatalog', () => ({
    moduleCatalog: {
        loadModules: loadModulesMock,
        routeRecords: computed(() => []),
        markRoutesRegistered: markRoutesRegisteredMock,
        isModuleEnabled: isModuleEnabledMock
    }
}));

vi.mock('@/core/auth/accessStore', () => ({
    accessStore: {
        hasPermission: hasPermissionMock
    }
}));

describe('router guards', () => {
    beforeEach(() => {
        initializeMock.mockReset();
        settingsInitializeMock.mockReset();
        loadModulesMock.mockReset();
        markRoutesRegisteredMock.mockReset();
        hasPermissionMock.mockReset();
        isModuleEnabledMock.mockReset();
        isAuthenticated.value = false;
        hasPermissionMock.mockReturnValue(true);
        isModuleEnabledMock.mockReturnValue(true);
        window.history.replaceState({}, '', '/');
    });

    it('redirects unauthenticated users to login with redirect param', async () => {
        vi.resetModules();
        const router = (await import('./index')).default;

        await router.push('/admin/settings');

        expect(initializeMock).toHaveBeenCalled();
        expect(router.currentRoute.value.name).toBe('login');
        expect(router.currentRoute.value.query.redirect).toBe('/admin/settings');
    }, 15000);

    it('redirects authenticated users away from login to dashboard', async () => {
        isAuthenticated.value = true;
        vi.resetModules();
        const router = (await import('./index')).default;

        await router.push('/auth/login');

        expect(settingsInitializeMock).toHaveBeenCalled();
        expect(loadModulesMock).toHaveBeenCalled();
        expect(router.currentRoute.value.name).toBe('dashboard');
    });

    it('sends users without permission to access denied', async () => {
        isAuthenticated.value = true;
        hasPermissionMock.mockReturnValue(false);
        vi.resetModules();
        const router = (await import('./index')).default;

        await router.push('/admin/settings');

        expect(router.currentRoute.value.name).toBe('accessDenied');
    });

    it('redirects to modules screen when a protected module is disabled', async () => {
        isAuthenticated.value = true;
        isModuleEnabledMock.mockReturnValue(false);
        vi.resetModules();
        const router = (await import('./index')).default;

        router.addRoute('app-shell', {
            path: '/demo/protected-route',
            name: 'demo-protected-route',
            meta: {
                requiresAuth: true,
                moduleKey: 'demo-platform'
            },
            component: {
                template: '<div />'
            }
        });

        await router.push('/demo/protected-route');

        expect(router.currentRoute.value.name).toBe('system-modules');
    });
});
