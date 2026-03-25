import api, { setApiAccessToken } from '@/service/api';
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import { notificationStore } from '@/core/notifications/notificationStore';
import { computed, reactive } from 'vue';

const STORAGE_KEY = 'stackbase.auth';

const state = reactive({
    token: null,
    user: null,
    switchingOrganization: false,
    initializing: false,
    initialized: false
});

function persistSession() {
    if (state.token && state.user) {
        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                token: state.token,
                user: state.user
            })
        );

        return;
    }

    localStorage.removeItem(STORAGE_KEY);
}

function restoreSession() {
    const raw = localStorage.getItem(STORAGE_KEY);

    if (!raw) {
        return;
    }

    try {
        const payload = JSON.parse(raw);
        state.token = payload.token ?? null;
        state.user = payload.user ?? null;
        setApiAccessToken(state.token);
    } catch {
        clearSession();
    }
}

function clearSession() {
    state.token = null;
    state.user = null;
    setApiAccessToken(null);
    moduleCatalog.reset();
    notificationStore.reset();
    persistSession();
}

async function fetchMe() {
    if (!state.token) {
        return null;
    }

    const response = await api.get('/v1/auth/me');
    state.user = response.data.datos;
    persistSession();

    return state.user;
}

async function initialize() {
    if (state.initialized || state.initializing) {
        return;
    }

    state.initializing = true;

    try {
        restoreSession();

        if (state.token) {
            try {
                await fetchMe();
            } catch {
                clearSession();
            }
        }
    } finally {
        state.initialized = true;
        state.initializing = false;
    }
}

async function login(credentials) {
    const response = await api.post('/v1/auth/login', credentials);
    state.token = response.data.datos.token;
    state.user = response.data.datos.user;
    setApiAccessToken(state.token);
    persistSession();

    return state.user;
}

async function register(payload) {
    const response = await api.post('/v1/auth/register', payload);
    state.token = response.data.datos.token;
    state.user = response.data.datos.user;
    setApiAccessToken(state.token);
    persistSession();
    await notificationStore.loadNotifications();

    return state.user;
}

async function logout() {
    try {
        if (state.token) {
            await api.post('/v1/auth/logout');
        }
    } finally {
        clearSession();
    }
}

async function switchActiveOrganization(organizationId) {
    state.switchingOrganization = true;

    try {
        const response = await api.patch('/v1/auth/active-organization', {
            organizacion_id: organizationId
        });

        state.user = response.data.datos;
        persistSession();
        await notificationStore.loadNotifications();

        return state.user;
    } finally {
        state.switchingOrganization = false;
    }
}

export const authStore = {
    state,
    isAuthenticated: computed(() => Boolean(state.token && state.user)),
    organizations: computed(() => state.user?.organizaciones ?? []),
    activeOrganization: computed(() => state.user?.organizacion_activa ?? null),
    hasPermission(permission) {
        return Boolean(state.user?.permissions?.includes(permission));
    },
    hasRole(role) {
        return Boolean(state.user?.roles?.includes(role));
    },
    initialize,
    login,
    register,
    logout,
    switchActiveOrganization,
    fetchMe,
    clearSession
};
