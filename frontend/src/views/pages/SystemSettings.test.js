import { flushPromises, mount } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { primeVueStubs } from '@/test/uiStubs';

const toastAddMock = vi.fn();
const initializeMock = vi.fn();
const updateGlobalMock = vi.fn();
const updateOrganizationMock = vi.fn();

const globalSettings = ref([
    {
        key: 'ui_preset',
        label: 'Preset',
        type: 'select',
        value: 'Aura',
        hidden: false,
        options: [
            { label: 'Aura', value: 'Aura' },
            { label: 'Lara', value: 'Lara' }
        ]
    },
    {
        key: 'ui_primary_color',
        label: 'Color primario',
        type: 'select',
        value: 'emerald',
        hidden: false,
        options: [
            { label: 'Emerald', value: 'emerald' },
            { label: 'Sky', value: 'sky' }
        ]
    },
    {
        key: 'ui_surface_palette',
        label: 'Surface',
        type: 'select',
        value: 'slate',
        hidden: false,
        options: [{ label: 'Slate', value: 'slate' }]
    },
    {
        key: 'ui_menu_mode',
        label: 'Menu',
        type: 'select',
        value: 'static',
        hidden: false,
        options: [
            { label: 'Lateral fijo', value: 'static' },
            { label: 'Overlay', value: 'overlay' }
        ]
    }
]);

const organizationSettings = ref([
    {
        key: 'locale',
        label: 'Locale',
        type: 'select',
        value: 'es-BO',
        hidden: false,
        options: [{ label: 'Español (Bolivia)', value: 'es-BO' }]
    }
]);

vi.mock('primevue/usetoast', () => ({
    useToast: () => ({
        add: toastAddMock
    })
}));

vi.mock('@/layout/composables/layoutAppearance', () => ({
    primaryColorOptions: [
        { name: 'emerald', palette: { 500: '#10b981' } },
        { name: 'sky', palette: { 500: '#0ea5e9' } }
    ],
    surfacePaletteOptions: [{ name: 'slate', palette: { 0: '#ffffff', 200: '#e2e8f0', 700: '#334155' } }]
}));

vi.mock('@/core/settings/settingsStore', () => ({
    settingsStore: {
        initialize: initializeMock,
        updateGlobal: updateGlobalMock,
        updateOrganization: updateOrganizationMock,
        globalSettings,
        organizationSettings
    }
}));

describe('SystemSettings', () => {
    beforeEach(() => {
        toastAddMock.mockReset();
        initializeMock.mockReset();
        updateGlobalMock.mockReset();
        updateOrganizationMock.mockReset();
        initializeMock.mockResolvedValue();
        updateGlobalMock.mockResolvedValue(globalSettings.value);
        updateOrganizationMock.mockResolvedValue(organizationSettings.value);
    });

    it('carga settings y permite guardar la apariencia global', async () => {
        const { default: SystemSettings } = await import('./SystemSettings.vue');
        const wrapper = mount(SystemSettings, {
            global: {
                stubs: primeVueStubs()
            }
        });

        await flushPromises();

        expect(initializeMock).toHaveBeenCalledWith(true);
        expect(wrapper.text()).toContain('Apariencia global');

        const saveButton = wrapper.findAll('button').find((button) => button.text().includes('Guardar'));

        await saveButton.trigger('click');
        await flushPromises();

        expect(updateGlobalMock).toHaveBeenCalled();
        expect(toastAddMock).toHaveBeenCalled();
    });
});
