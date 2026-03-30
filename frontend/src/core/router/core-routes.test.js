import { describe, expect, it } from 'vitest';

import { coreRoutes } from './core-routes';

function findChildRoute(name) {
    const appShell = coreRoutes.find((route) => route.name === 'app-shell');

    return appShell?.children?.find((child) => child.name === name) ?? null;
}

describe('core routes permission metadata', () => {
    it('uses fine-grained permissions for operational administration screens', () => {
        expect(findChildRoute('operations-overview')?.meta?.permissionKey).toBe('operations.view');
        expect(findChildRoute('usage-metrics')?.meta?.permissionKey).toBe('metrics.view');
        expect(findChildRoute('security-logs')?.meta?.permissionKey).toBe('security.logs.view');
        expect(findChildRoute('error-logs')?.meta?.permissionKey).toBe('error-logs.view');
    });

    it('uses view permissions for administrative screens and reserves manage permissions for actions', () => {
        expect(findChildRoute('system-modules')?.meta?.permissionKey).toBe('modules.view');
        expect(findChildRoute('system-webhooks')?.meta?.permissionKey).toBe('integrations.view');
        expect(findChildRoute('system-settings')?.meta?.permissionKey).toBe('settings.view');
        expect(findChildRoute('user-administration')?.meta?.permissionKey).toBe('users.view');
        expect(findChildRoute('role-administration')?.meta?.permissionKey).toBe('roles.view');
    });
});
