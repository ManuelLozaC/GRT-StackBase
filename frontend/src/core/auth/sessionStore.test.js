import { beforeEach, describe, expect, it, vi } from 'vitest';

const apiMock = {
    get: vi.fn(),
    post: vi.fn()
};

const setApiAccessTokenMock = vi.fn();
const moduleCatalogResetMock = vi.fn();
const moduleCatalogLoadModulesMock = vi.fn();
const notificationResetMock = vi.fn();
const notificationLoadMock = vi.fn();
const settingsResetMock = vi.fn();
const settingsInitializeMock = vi.fn();

vi.mock('@/service/api', () => ({
    default: apiMock,
    setApiAccessToken: setApiAccessTokenMock
}));

vi.mock('@/core/modules/moduleCatalog', () => ({
    moduleCatalog: {
        reset: moduleCatalogResetMock,
        loadModules: moduleCatalogLoadModulesMock
    }
}));

vi.mock('@/core/notifications/notificationStore', () => ({
    notificationStore: {
        reset: notificationResetMock,
        loadNotifications: notificationLoadMock
    }
}));

vi.mock('@/core/settings/settingsStore', () => ({
    settingsStore: {
        reset: settingsResetMock,
        initialize: settingsInitializeMock
    }
}));

describe('sessionStore', () => {
    beforeEach(async () => {
        vi.resetModules();
        apiMock.get.mockReset();
        apiMock.post.mockReset();
        setApiAccessTokenMock.mockReset();
        moduleCatalogResetMock.mockReset();
        moduleCatalogLoadModulesMock.mockReset();
        notificationResetMock.mockReset();
        notificationLoadMock.mockReset();
        settingsResetMock.mockReset();
        settingsInitializeMock.mockReset();
        localStorage.clear();
    });

    it('persists login session and restores authenticated user', async () => {
        const { sessionStore } = await import('./sessionStore');

        apiMock.post.mockResolvedValueOnce({
            data: {
                datos: {
                    token: 'token-1',
                    user: {
                        id: 7,
                        email: 'user@example.com'
                    }
                }
            }
        });

        const user = await sessionStore.login({
            email: 'user@example.com',
            password: 'secret',
            device_name: 'browser'
        });

        expect(user.email).toBe('user@example.com');
        expect(sessionStore.state.token).toBe('token-1');
        expect(setApiAccessTokenMock).toHaveBeenCalledWith('token-1');
        expect(JSON.parse(localStorage.getItem('stackbase.auth'))).toEqual({
            token: 'token-1',
            user: {
                id: 7,
                email: 'user@example.com'
            }
        });
    });

    it('initializes from persisted session and refreshes /me', async () => {
        localStorage.setItem(
            'stackbase.auth',
            JSON.stringify({
                token: 'persisted-token',
                user: {
                    id: 1,
                    email: 'old@example.com'
                }
            })
        );

        apiMock.get.mockResolvedValueOnce({
            data: {
                datos: {
                    id: 1,
                    email: 'fresh@example.com',
                    empresas: []
                }
            }
        });

        const { sessionStore } = await import('./sessionStore');

        await sessionStore.initialize();

        expect(apiMock.get).toHaveBeenCalledWith('/v1/auth/me');
        expect(sessionStore.state.user.email).toBe('fresh@example.com');
        expect(setApiAccessTokenMock).toHaveBeenCalledWith('persisted-token');
    });

    it('clears session and dependent stores when logout finishes', async () => {
        const { sessionStore } = await import('./sessionStore');

        sessionStore.setSession('token-logout', {
            id: 3,
            email: 'logout@example.com'
        });

        apiMock.post.mockResolvedValueOnce({
            data: {
                datos: null
            }
        });

        await sessionStore.logout();

        expect(apiMock.post).toHaveBeenCalledWith('/v1/auth/logout');
        expect(sessionStore.state.token).toBeNull();
        expect(sessionStore.state.user).toBeNull();
        expect(moduleCatalogResetMock).toHaveBeenCalled();
        expect(notificationResetMock).toHaveBeenCalled();
        expect(settingsResetMock).toHaveBeenCalled();
        expect(localStorage.getItem('stackbase.auth')).toBeNull();
        expect(setApiAccessTokenMock).toHaveBeenLastCalledWith(null);
    });
});
