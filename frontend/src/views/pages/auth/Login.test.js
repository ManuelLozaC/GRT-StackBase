import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { primeVueStubs } from '@/test/uiStubs';

const pushMock = vi.fn();
const loginMock = vi.fn();
const toastAddMock = vi.fn();
const routeMock = {
    query: {
        redirect: '/dashboard'
    }
};

vi.mock('vue-router', () => ({
    useRouter: () => ({
        push: pushMock
    }),
    useRoute: () => routeMock
}));

vi.mock('primevue/usetoast', () => ({
    useToast: () => ({
        add: toastAddMock
    })
}));

vi.mock('@/core/auth/sessionStore', () => ({
    sessionStore: {
        login: loginMock
    }
}));

describe('Login view', () => {
    beforeEach(() => {
        pushMock.mockReset();
        loginMock.mockReset();
        toastAddMock.mockReset();
        routeMock.query.redirect = '/dashboard';
    });

    it('envia las credenciales y redirige al destino indicado', async () => {
        loginMock.mockResolvedValue({});

        const { default: Login } = await import('./Login.vue');
        const wrapper = mount(Login, {
            global: {
                stubs: primeVueStubs()
            }
        });

        await wrapper.find('input[type="text"]').setValue('mloza');
        await wrapper.find('input[type="password"]').setValue('admin1984!');
        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(loginMock).toHaveBeenCalledWith({
            email: 'mloza',
            password: 'admin1984!',
            device_name: 'frontend-remember'
        });
        expect(pushMock).toHaveBeenCalledWith('/dashboard');
    });

    it('muestra un mensaje amigable cuando el login falla', async () => {
        loginMock.mockRejectedValue({
            response: {
                data: {
                    mensaje: 'Credenciales invalidas'
                }
            }
        });

        const { default: Login } = await import('./Login.vue');
        const wrapper = mount(Login, {
            global: {
                stubs: primeVueStubs()
            }
        });

        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(wrapper.text()).toContain('Credenciales invalidas');
        expect(toastAddMock).toHaveBeenCalled();
    });
});
