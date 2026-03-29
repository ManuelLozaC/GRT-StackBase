import { beforeEach, describe, expect, it, vi } from 'vitest';

const apiMock = {
    patch: vi.fn()
};

const loadNotificationsMock = vi.fn();

vi.mock('@/service/api', () => ({
    default: apiMock
}));

vi.mock('@/core/notifications/notificationStore', () => ({
    notificationStore: {
        loadNotifications: loadNotificationsMock
    }
}));

describe('tenantStore', () => {
    beforeEach(async () => {
        vi.resetModules();
        apiMock.patch.mockReset();
        loadNotificationsMock.mockReset();
        localStorage.clear();
    });

    it('switches active organization and refreshes notifications', async () => {
        const { sessionStore } = await import('./sessionStore');
        const { tenantStore } = await import('./tenantStore');

        sessionStore.setUser({
            id: 5,
            empresas: [{ id: 10, nombre: 'Empresa A' }],
            empresa_activa: { id: 10, nombre: 'Empresa A' },
            asignaciones_laborales_disponibles: []
        });

        apiMock.patch.mockResolvedValueOnce({
            data: {
                datos: {
                    id: 5,
                    empresas: [{ id: 11, nombre: 'Empresa B' }],
                    empresa_activa: { id: 11, nombre: 'Empresa B' },
                    asignaciones_laborales_disponibles: []
                }
            }
        });

        const user = await tenantStore.switchActiveOrganization(11);

        expect(apiMock.patch).toHaveBeenCalledWith('/v1/auth/active-company', {
            empresa_id: 11
        });
        expect(loadNotificationsMock).toHaveBeenCalled();
        expect(user.empresa_activa.id).toBe(11);
        expect(tenantStore.activeOrganization.value.nombre).toBe('Empresa B');
    });

    it('switches active work assignment and exposes fallback organization aliases', async () => {
        const { sessionStore } = await import('./sessionStore');
        const { tenantStore } = await import('./tenantStore');

        sessionStore.setUser({
            id: 9,
            organizaciones: [{ id: 21, nombre: 'Legacy Org' }],
            organizacion_activa: { id: 21, nombre: 'Legacy Org' },
            asignacion_laboral_activa: { id: 30, etiqueta_contexto: 'Actual' },
            asignaciones_laborales_disponibles: [{ id: 30, etiqueta_contexto: 'Actual' }]
        });

        apiMock.patch.mockResolvedValueOnce({
            data: {
                datos: {
                    id: 9,
                    organizaciones: [{ id: 21, nombre: 'Legacy Org' }],
                    organizacion_activa: { id: 21, nombre: 'Legacy Org' },
                    asignacion_laboral_activa: { id: 31, etiqueta_contexto: 'Sucursal Centro' },
                    asignaciones_laborales_disponibles: [{ id: 31, etiqueta_contexto: 'Sucursal Centro' }]
                }
            }
        });

        const user = await tenantStore.switchActiveWorkAssignment(31);

        expect(apiMock.patch).toHaveBeenCalledWith('/v1/auth/active-work-assignment', {
            asignacion_laboral_id: 31
        });
        expect(user.asignacion_laboral_activa.id).toBe(31);
        expect(tenantStore.organizations.value[0].nombre).toBe('Legacy Org');
        expect(tenantStore.activeWorkAssignment.value.etiqueta_contexto).toBe('Sucursal Centro');
    });
});
