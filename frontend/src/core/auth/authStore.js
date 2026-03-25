import { computed } from 'vue';
import { accessStore } from './accessStore';
import { sessionStore } from './sessionStore';
import { tenantStore } from './tenantStore';

export const authStore = {
    isAuthenticated: sessionStore.isAuthenticated,
    organizations: tenantStore.organizations,
    activeOrganization: tenantStore.activeOrganization,
    user: sessionStore.user,
    token: sessionStore.token,
    initializing: computed(() => sessionStore.state.initializing),
    initialized: computed(() => sessionStore.state.initialized),
    switchingOrganization: computed(() => tenantStore.state.switchingOrganization),
    hasPermission: accessStore.hasPermission,
    hasRole: accessStore.hasRole,
    initialize: sessionStore.initialize,
    login: sessionStore.login,
    register: sessionStore.register,
    logout: sessionStore.logout,
    switchActiveOrganization: tenantStore.switchActiveOrganization,
    fetchMe: sessionStore.fetchMe,
    clearSession: sessionStore.clearSession
};
