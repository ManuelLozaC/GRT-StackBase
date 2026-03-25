import api, { setApiAccessToken } from '@/service/api';
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import { notificationStore } from '@/core/notifications/notificationStore';
import { settingsStore } from '@/core/settings/settingsStore';
import { computed, reactive } from 'vue';

const STORAGE_KEY = 'stackbase.auth';

const state = reactive({
    token: null,
    user: null,
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

function setSession(token, user) {
    state.token = token;
    state.user = user;
    setApiAccessToken(token);
    persistSession();
}

function setUser(user) {
    state.user = user;
    persistSession();
}

function clearSession() {
    state.token = null;
    state.user = null;
    setApiAccessToken(null);
    moduleCatalog.reset();
    notificationStore.reset();
    settingsStore.reset();
    persistSession();
}

async function fetchMe() {
    if (!state.token) {
        return null;
    }

    const response = await api.get('/v1/auth/me');
    setUser(response.data.datos);

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
        if (state.token) {
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
    isAuthenticated: computed(() => Boolean(state.token && state.user)),
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
