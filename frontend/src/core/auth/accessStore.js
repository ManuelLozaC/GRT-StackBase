import { computed } from 'vue';
import { sessionStore } from './sessionStore';

const roles = computed(() => sessionStore.state.user?.roles ?? []);
const permissions = computed(() => sessionStore.state.user?.permissions ?? []);
const contextPermissions = computed(() => sessionStore.state.user?.context_permissions ?? []);
const effectivePermissions = computed(() => Array.from(new Set([...permissions.value, ...contextPermissions.value])));

function hasPermission(permission) {
    return effectivePermissions.value.includes(permission);
}

function hasRole(role) {
    return roles.value.includes(role);
}

export const accessStore = {
    roles,
    permissions,
    contextPermissions,
    effectivePermissions,
    hasPermission,
    hasRole
};
