import { computed } from 'vue';
import { sessionStore } from './sessionStore';

const roles = computed(() => sessionStore.state.user?.roles ?? []);
const permissions = computed(() => sessionStore.state.user?.permissions ?? []);

function hasPermission(permission) {
    return permissions.value.includes(permission);
}

function hasRole(role) {
    return roles.value.includes(role);
}

export const accessStore = {
    roles,
    permissions,
    hasPermission,
    hasRole
};
