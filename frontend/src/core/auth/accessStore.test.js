import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('./sessionStore', () => ({
    sessionStore: {
        state: {
            user: null
        }
    }
}));

describe('accessStore', () => {
    beforeEach(async () => {
        vi.resetModules();
        const { sessionStore } = await import('./sessionStore');
        sessionStore.state.user = null;
    });

    it('merges global and contextual permissions without duplicates', async () => {
        const { sessionStore } = await import('./sessionStore');
        const { accessStore } = await import('./accessStore');

        sessionStore.state.user = {
            roles: ['admin'],
            permissions: ['settings.manage', 'users.manage_roles'],
            context_permissions: ['users.manage_roles', 'security.manage']
        };

        expect(accessStore.roles.value).toEqual(['admin']);
        expect(accessStore.effectivePermissions.value).toEqual(['settings.manage', 'users.manage_roles', 'security.manage']);
        expect(accessStore.hasPermission('security.manage')).toBe(true);
        expect(accessStore.hasRole('admin')).toBe(true);
    });

    it('returns false when the user has no matching role or permission', async () => {
        const { sessionStore } = await import('./sessionStore');
        const { accessStore } = await import('./accessStore');

        sessionStore.state.user = {
            roles: ['viewer'],
            permissions: ['documentation.read'],
            context_permissions: []
        };

        expect(accessStore.hasPermission('settings.manage')).toBe(false);
        expect(accessStore.hasRole('admin')).toBe(false);
    });
});
