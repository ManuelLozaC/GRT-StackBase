import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { primeVueStubs } from '@/test/uiStubs';

const pushMock = vi.fn();
const toastAddMock = vi.fn();
const impersonateMock = vi.fn();

const apiGetMock = vi.fn();
const apiPostMock = vi.fn();
const apiPatchMock = vi.fn();

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

vi.mock('@/service/api', () => ({
    default: {
        get: apiGetMock,
        post: apiPostMock,
        patch: apiPatchMock
    }
}));

vi.mock('@/core/auth/sessionStore', () => ({
    sessionStore: {
        state: {
            user: {
                id: 1
            }
        },
        impersonate: impersonateMock
    }
}));

describe('UserAdministration', () => {
    beforeEach(() => {
        pushMock.mockReset();
        toastAddMock.mockReset();
        impersonateMock.mockReset();
        apiGetMock.mockReset();
        apiPostMock.mockReset();
        apiPatchMock.mockReset();

        apiGetMock
            .mockResolvedValueOnce({
                data: {
                    datos: [],
                    meta: {
                        available_roles: ['admin', 'operaciones'],
                        available_personas: [
                            {
                                id: 15,
                                label: 'Maria Suarez',
                                correo: 'maria@grt.com.bo',
                                telefono: '+59170000000'
                            }
                        ]
                    }
                }
            })
            .mockResolvedValueOnce({
                data: {
                    datos: [],
                    meta: {
                        available_roles: ['admin', 'operaciones'],
                        available_personas: [
                            {
                                id: 15,
                                label: 'Maria Suarez',
                                correo: 'maria@grt.com.bo',
                                telefono: '+59170000000'
                            }
                        ]
                    }
                }
            });
    });

    it('crea un usuario nuevo desde el formulario principal', async () => {
        apiPostMock.mockResolvedValue({
            data: {
                datos: {
                    id: 22
                }
            }
        });

        const { default: UserAdministration } = await import('./UserAdministration.vue');
        const wrapper = mount(UserAdministration, {
            global: {
                stubs: primeVueStubs()
            }
        });

        await flushPromises();

        const newUserButton = wrapper.findAll('button').find((button) => button.text().includes('Nuevo usuario'));

        await newUserButton.trigger('click');
        await flushPromises();

        const selects = wrapper.findAll('select');
        await selects[0].setValue('15');

        const textInputs = wrapper.findAll('input[type="text"]');
        await textInputs[0].setValue('maria.suarez');
        await textInputs[1].setValue('maria.acceso@grt.com.bo');

        const passwordInputs = wrapper.findAll('input[type="password"]');
        await passwordInputs[0].setValue('Admin2026');
        await passwordInputs[1].setValue('Admin2026');

        const createButton = wrapper.findAll('button').find((button) => button.text().includes('Crear usuario'));

        await createButton.trigger('click');
        await flushPromises();

        expect(apiPostMock).toHaveBeenCalledWith('/v1/users', {
            persona_id: 15,
            name: null,
            alias: 'maria.suarez',
            email: 'maria.acceso@grt.com.bo',
            activo: true,
            roles: [],
            password: 'Admin2026',
            password_confirmation: 'Admin2026'
        });
    });
});
