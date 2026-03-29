import { beforeEach, describe, expect, it, vi } from 'vitest';

const apiMock = {
    get: vi.fn(),
    patch: vi.fn()
};

const applyVisualSettingsMock = vi.fn();

vi.mock('@/service/api', () => ({
    default: apiMock
}));

vi.mock('@/layout/composables/layout', () => ({
    useLayout: () => ({
        applyVisualSettings: applyVisualSettingsMock
    })
}));

describe('settingsStore', () => {
    beforeEach(async () => {
        vi.resetModules();
        apiMock.get.mockReset();
        apiMock.patch.mockReset();
        applyVisualSettingsMock.mockReset();
    });

    it('bootstraps settings and applies global appearance', async () => {
        apiMock.get.mockResolvedValueOnce({
            data: {
                datos: {
                    global: [
                        { key: 'ui_preset', value: 'Lara' },
                        { key: 'ui_primary_color', value: 'blue' },
                        { key: 'ui_surface_palette', value: 'zinc' },
                        { key: 'ui_menu_mode', value: 'overlay' }
                    ],
                    company: [
                        { key: 'locale', value: 'es-BO' },
                        { key: 'date_format', value: 'DD/MM/YYYY' },
                        { key: 'currency_code', value: 'BOB' },
                        { key: 'timezone', value: 'America/La_Paz' }
                    ],
                    user: [{ key: 'theme', value: 'dark' }],
                    feature_flags: {
                        feature_notifications_push: true
                    }
                }
            }
        });

        const { settingsStore } = await import('./settingsStore');

        await settingsStore.initialize();

        expect(apiMock.get).toHaveBeenCalledWith('/v1/settings/bootstrap');
        expect(settingsStore.featureFlags.value.feature_notifications_push).toBe(true);
        expect(applyVisualSettingsMock).toHaveBeenCalledWith({
            theme: 'dark',
            preset: 'Lara',
            primary: 'blue',
            surface: 'zinc',
            menuMode: 'overlay'
        });
    });

    it('merges organization defaults with user inherit preferences', async () => {
        const { settingsStore } = await import('./settingsStore');

        settingsStore.state.organization = [
            { key: 'locale', value: 'es-BO' },
            { key: 'date_format', value: 'DD/MM/YYYY' },
            { key: 'currency_code', value: 'BOB' },
            { key: 'timezone', value: 'America/La_Paz' }
        ];
        settingsStore.state.user = [
            { key: 'locale', value: 'inherit' },
            { key: 'date_format', value: 'YYYY-MM-DD' },
            { key: 'currency_code', value: 'inherit' },
            { key: 'timezone', value: 'UTC' }
        ];

        expect(settingsStore.resolvedPreferences.value).toEqual({
            locale: 'es-BO',
            date_format: 'YYYY-MM-DD',
            currency_code: 'BOB',
            timezone: 'UTC'
        });
    });

    it('updates global settings and reapplies layout appearance', async () => {
        const { settingsStore } = await import('./settingsStore');

        apiMock.patch.mockResolvedValueOnce({
            data: {
                datos: [
                    { key: 'ui_preset', value: 'Nora' },
                    { key: 'ui_primary_color', value: 'emerald' },
                    { key: 'ui_surface_palette', value: 'slate' },
                    { key: 'ui_menu_mode', value: 'static' }
                ]
            }
        });

        settingsStore.state.user = [{ key: 'theme', value: 'light' }];

        const updated = await settingsStore.updateGlobal({
            ui_preset: 'Nora'
        });

        expect(apiMock.patch).toHaveBeenCalledWith('/v1/settings/global', {
            ui_preset: 'Nora'
        });
        expect(updated[0].value).toBe('Nora');
        expect(applyVisualSettingsMock).toHaveBeenCalledWith({
            theme: 'light',
            preset: 'Nora',
            primary: 'emerald',
            surface: 'slate',
            menuMode: 'static'
        });
    });
});
