import api from '@/service/api';
import { resolveModuleView } from '@/modules/registry';
import { computed, reactive } from 'vue';

const state = reactive({
    items: [],
    loading: false,
    loaded: false,
    registeredRouteNames: []
});

const enabledKeys = computed(() => {
    return state.items.filter((item) => item.enabled).map((item) => item.key);
});

function resolveRoutePermission(moduleItem, route) {
    if (route.meta?.permissionKey) {
        return route.meta.permissionKey;
    }

    if (Array.isArray(moduleItem.permissions) && moduleItem.permissions.length === 1) {
        return moduleItem.permissions[0];
    }

    return null;
}

const menuTree = computed(() => {
    return state.items
        .map((moduleItem) => {
            const routes = moduleItem.frontend?.routes ?? [];
            const items = routes
                .filter((route) => route.menu)
                .map((route) => ({
                    label: route.menu.label,
                    icon: route.menu.icon,
                    to: route.path,
                    moduleKey: moduleItem.key,
                    permissionKey: resolveRoutePermission(moduleItem, route)
                }));

            if (items.length === 0) {
                return null;
            }

            return {
                label: moduleItem.frontend?.navigation?.label ?? moduleItem.name,
                moduleKey: moduleItem.key,
                items
            };
        })
        .filter(Boolean);
});

const routeRecords = computed(() => {
    return state.items.flatMap((moduleItem) => {
        const routes = moduleItem.frontend?.routes ?? [];

        return routes
            .map((route) => {
                const component = resolveModuleView(moduleItem.key, route.view);

                if (!component) {
                    return null;
                }

                return {
                    path: route.path,
                    name: route.name,
                    meta: {
                        moduleKey: moduleItem.key,
                        requiresAuth: true,
                        ...(route.meta ?? {}),
                        permissionKey: resolveRoutePermission(moduleItem, route)
                    },
                    component
                };
            })
            .filter(Boolean);
    });
});

async function loadModules(force = false) {
    if (state.loading) {
        return;
    }

    if (state.loaded && !force) {
        return;
    }

    state.loading = true;

    try {
        const response = await api.get('/v1/modules');
        state.items = response.data.datos ?? [];
        state.loaded = true;
    } finally {
        state.loading = false;
    }
}

async function updateModuleStatus(moduleKey, enabled) {
    await api.patch(`/v1/modules/${moduleKey}`, {
        enabled
    });

    await loadModules(true);

    return state.items.find((item) => item.key === moduleKey) ?? null;
}

function isModuleEnabled(moduleKey) {
    return enabledKeys.value.includes(moduleKey);
}

function markRoutesRegistered(routeNames) {
    state.registeredRouteNames = [...new Set([...state.registeredRouteNames, ...routeNames])];
}

function isRouteRegistered(routeName) {
    return state.registeredRouteNames.includes(routeName);
}

function reset() {
    state.items = [];
    state.loading = false;
    state.loaded = false;
    state.registeredRouteNames = [];
}

export const moduleCatalog = {
    state,
    enabledKeys,
    menuTree,
    routeRecords,
    loadModules,
    updateModuleStatus,
    isModuleEnabled,
    markRoutesRegistered,
    isRouteRegistered,
    reset
};
