import { flushPromises, mount } from '@vue/test-utils';
import { nextTick, ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { primeVueStubs } from '@/test/uiStubs';

const pushMock = vi.fn();
const toastAddMock = vi.fn();
const logoutMock = vi.fn();
const leaveImpersonationMock = vi.fn();
const loadNotificationsMock = vi.fn();
const updateUserMock = vi.fn();
const switchOrganizationMock = vi.fn();
const switchWorkAssignmentMock = vi.fn();

const activeOrganization = ref({ id: 7, nombre: 'GRT SRL' });
const organizations = ref([
    { id: 7, nombre: 'GRT SRL' },
    { id: 8, nombre: 'Acme' }
]);
const activeWorkAssignment = ref({ id: 11, etiqueta_contexto: 'Gerente | Santa Cruz' });
const workAssignments = ref([
    { id: 11, etiqueta_contexto: 'Gerente | Santa Cruz' },
    { id: 12, etiqueta_contexto: 'Ventas | Cochabamba' }
]);
const unreadCount = ref(3);
const isDarkTheme = ref(false);

vi.mock('vue-router', () => ({
    useRouter: () => ({
        push: pushMock
    })
}));

vi.mock('primevue/usetoast', () => ({
    useToast: () => ({
        add: toastAddMock
    })
}));

vi.mock('@/layout/composables/layout', () => ({
    useLayout: () => ({
        toggleMenu: vi.fn(),
        isDarkTheme
    })
}));

vi.mock('@/core/auth/sessionStore', () => ({
    sessionStore: {
        state: {
            user: {
                id: 1,
                name: 'Manuel Loza',
                email: 'mloza@grt.com.bo',
                impersonation: { active: false, impersonated_by: null }
            }
        },
        logout: logoutMock,
        leaveImpersonation: leaveImpersonationMock
    }
}));

vi.mock('@/core/auth/tenantStore', () => ({
    tenantStore: {
        state: {
            switchingOrganization: false,
            switchingWorkAssignment: false
        },
        organizations,
        activeOrganization,
        availableWorkAssignments: workAssignments,
        activeWorkAssignment,
        switchActiveOrganization: switchOrganizationMock,
        switchActiveWorkAssignment: switchWorkAssignmentMock
    }
}));

vi.mock('@/core/auth/accessStore', () => ({
    accessStore: {
        hasPermission: (permission) => ['settings.manage', 'modules.manage'].includes(permission)
    }
}));

vi.mock('@/core/notifications/notificationStore', () => ({
    notificationStore: {
        unreadCount,
        loadNotifications: loadNotificationsMock
    }
}));

vi.mock('@/core/settings/settingsStore', () => ({
    settingsStore: {
        updateUser: updateUserMock
    }
}));

describe('AppTopbar', () => {
    beforeEach(() => {
        pushMock.mockReset();
        toastAddMock.mockReset();
        logoutMock.mockReset();
        leaveImpersonationMock.mockReset();
        loadNotificationsMock.mockReset();
        updateUserMock.mockReset();
        switchOrganizationMock.mockReset();
        switchWorkAssignmentMock.mockReset();
        activeOrganization.value = { id: 7, nombre: 'GRT SRL' };
        activeWorkAssignment.value = { id: 11, etiqueta_contexto: 'Gerente | Santa Cruz' };
        isDarkTheme.value = false;
    });

    it('permite abrir el modal de empresa y contexto desde el menu de cuenta', async () => {
        const { default: AppTopbar } = await import('./AppTopbar.vue');
        const wrapper = mount(AppTopbar, {
            global: {
                stubs: primeVueStubs()
            }
        });

        await wrapper.find('.topbar-account-trigger').trigger('click');
        const contextButton = wrapper.findAll('button').find((button) => button.text().includes('Empresa y contexto'));

        await contextButton.trigger('click');
        await nextTick();

        expect(wrapper.text()).toContain('Empresa y contexto laboral');
    });

    it('actualiza la preferencia de tema desde la cabecera', async () => {
        updateUserMock.mockResolvedValue([]);

        const { default: AppTopbar } = await import('./AppTopbar.vue');
        const wrapper = mount(AppTopbar, {
            global: {
                stubs: primeVueStubs()
            }
        });

        await wrapper.find('.topbar-theme-button').trigger('click');

        expect(updateUserMock).toHaveBeenCalledWith({
            theme: 'dark'
        });
        expect(toastAddMock).toHaveBeenCalled();
    });

    it('cierra sesion desde el menu de cuenta', async () => {
        logoutMock.mockResolvedValue();

        const { default: AppTopbar } = await import('./AppTopbar.vue');
        const wrapper = mount(AppTopbar, {
            global: {
                stubs: primeVueStubs()
            }
        });

        await wrapper.find('.topbar-account-trigger').trigger('click');
        const logoutButton = wrapper.findAll('button').find((button) => button.text().includes('Cerrar sesion'));

        await logoutButton.trigger('click');
        await flushPromises();

        expect(logoutMock).toHaveBeenCalled();
        expect(pushMock).toHaveBeenCalledWith({ name: 'login' });
    });
});
