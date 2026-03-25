import api from '@/service/api';
import { useLayout } from '@/layout/composables/layout';
import { computed, reactive } from 'vue';

const state = reactive({
    initialized: false,
    loading: false,
    saving: false,
    global: [],
    organization: [],
    user: [],
    featureFlags: {}
});

function getSettingValue(scope, key, fallback = null) {
    const collection = state[scope] ?? [];
    const match = collection.find((item) => item.key === key);

    return match ? match.value : fallback;
}

function applyThemeFromSettings() {
    const { applyThemePreference } = useLayout();
    applyThemePreference(getSettingValue('user', 'theme', 'system'));
}

async function initialize(force = false) {
    if (state.initialized && !force) {
        return;
    }

    state.loading = true;

    try {
        const response = await api.get('/v1/settings/bootstrap');
        state.global = response.data.datos?.global ?? [];
        state.organization = response.data.datos?.organization ?? [];
        state.user = response.data.datos?.user ?? [];
        state.featureFlags = response.data.datos?.feature_flags ?? {};
        state.initialized = true;
        applyThemeFromSettings();
    } finally {
        state.loading = false;
    }
}

async function updateGlobal(payload) {
    state.saving = true;

    try {
        const response = await api.patch('/v1/settings/global', payload);
        state.global = response.data.datos ?? [];

        return state.global;
    } finally {
        state.saving = false;
    }
}

async function updateOrganization(payload) {
    state.saving = true;

    try {
        const response = await api.patch('/v1/settings/organization', payload);
        state.organization = response.data.datos ?? [];

        return state.organization;
    } finally {
        state.saving = false;
    }
}

async function updateUser(payload) {
    state.saving = true;

    try {
        const response = await api.patch('/v1/settings/me', payload);
        state.user = response.data.datos ?? [];
        applyThemeFromSettings();

        return state.user;
    } finally {
        state.saving = false;
    }
}

function reset() {
    state.initialized = false;
    state.loading = false;
    state.saving = false;
    state.global = [];
    state.organization = [];
    state.user = [];
    state.featureFlags = {};
}

export const settingsStore = {
    state,
    globalBanner: computed(() => ({
        enabled: Boolean(getSettingValue('global', 'app_banner_enabled', false)),
        message: getSettingValue('global', 'app_banner_message', ''),
        severity: getSettingValue('global', 'app_banner_severity', 'info')
    })),
    globalSettings: computed(() => state.global),
    organizationSettings: computed(() => state.organization),
    userSettings: computed(() => state.user),
    featureFlags: computed(() => state.featureFlags),
    userTheme: computed(() => getSettingValue('user', 'theme', 'system')),
    resolvedPreferences: computed(() => {
        const organizationLocale = getSettingValue('organization', 'locale', 'es-BO');
        const organizationDateFormat = getSettingValue('organization', 'date_format', 'DD/MM/YYYY');
        const organizationCurrency = getSettingValue('organization', 'currency_code', 'BOB');
        const organizationTimezone = getSettingValue('organization', 'timezone', 'America/La_Paz');
        const userLocale = getSettingValue('user', 'locale', 'inherit');
        const userDateFormat = getSettingValue('user', 'date_format', 'inherit');
        const userCurrency = getSettingValue('user', 'currency_code', 'inherit');
        const userTimezone = getSettingValue('user', 'timezone', 'inherit');

        return {
            locale: userLocale !== 'inherit' ? userLocale : organizationLocale,
            date_format: userDateFormat !== 'inherit' ? userDateFormat : organizationDateFormat,
            currency_code: userCurrency !== 'inherit' ? userCurrency : organizationCurrency,
            timezone: userTimezone !== 'inherit' ? userTimezone : organizationTimezone
        };
    }),
    initialize,
    updateGlobal,
    updateOrganization,
    updateUser,
    getSettingValue,
    reset
};
