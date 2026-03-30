import api, { setApiAccessToken } from '@/service/api';
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import { notificationStore } from '@/core/notifications/notificationStore';
import { settingsStore } from '@/core/settings/settingsStore';
import { computed, reactive } from 'vue';

const state = reactive({
    token: null,
    user: null,
    initializing: false,
    initialized: false
});

function setSession(token, user) {
    state.token = null;
    state.user = user;
    setApiAccessToken(null);
}

function setUser(user) {
    state.user = user;
}

function clearSession() {
    state.token = null;
    state.user = null;
    setApiAccessToken(null);
    moduleCatalog.reset();
    notificationStore.reset();
    settingsStore.reset();
}

async function fetchMe() {
    const response = await api.get('/v1/auth/me', {
        suppressUiError: true
    });
    setUser(response.data.datos);

    return state.user;
}

async function initialize() {
    if (state.initialized || state.initializing) {
        return;
    }

    state.initializing = true;

    try {
        try {
            await fetchMe();
        } catch {
            clearSession();
        }
    } finally {
        state.initialized = true;
        state.initializing = false;
    }
}

async function login(credentials) {
    const response = await api.post('/v1/auth/login', credentials);
    setSession(response.data.datos.token, response.data.datos.user);

    return state.user;
}

async function register(payload) {
    const response = await api.post('/v1/auth/register', payload);
    setSession(response.data.datos.token, response.data.datos.user);
    await notificationStore.loadNotifications();

    return state.user;
}

async function logout() {
    try {
        if (state.user) {
            await api.post('/v1/auth/logout');
        }
    } finally {
        clearSession();
    }
}

async function impersonate(userId) {
    const response = await api.post(`/v1/auth/impersonate/${userId}`);
    setSession(response.data.datos.token, response.data.datos.user);
    await Promise.all([settingsStore.initialize(true), moduleCatalog.loadModules(true), notificationStore.loadNotifications()]);

    return state.user;
}

async function leaveImpersonation() {
    const response = await api.post('/v1/auth/impersonation/leave');
    setSession(response.data.datos.token, response.data.datos.user);
    await Promise.all([settingsStore.initialize(true), moduleCatalog.loadModules(true), notificationStore.loadNotifications()]);

    return state.user;
}

export const sessionStore = {
    state,
    user: computed(() => state.user),
    token: computed(() => state.token),
    isAuthenticated: computed(() => Boolean(state.user)),
    initialize,
    fetchMe,
    login,
    register,
    logout,
    impersonate,
    leaveImpersonation,
    setSession,
    setUser,
    clearSession
};
