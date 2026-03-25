import { settingsStore } from '@/core/settings/settingsStore';

function resolvedPreference(key, fallback = null) {
    return settingsStore.resolvedPreferences.value[key] ?? fallback;
}

export function formatDateTime(value, options = {}) {
    if (!value) {
        return '-';
    }

    const locale = options.locale ?? resolvedPreference('locale', 'es-BO');
    const timezone = options.timezone ?? resolvedPreference('timezone', 'America/La_Paz');

    return new Intl.DateTimeFormat(locale, {
        dateStyle: options.dateStyle ?? 'medium',
        timeStyle: options.timeStyle ?? 'short',
        timeZone: timezone
    }).format(new Date(value));
}

export function formatCurrency(value, options = {}) {
    const amount = Number(value ?? 0);
    const locale = options.locale ?? resolvedPreference('locale', 'es-BO');
    const currency = options.currency ?? resolvedPreference('currency_code', 'BOB');

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        maximumFractionDigits: 2
    }).format(amount);
}
