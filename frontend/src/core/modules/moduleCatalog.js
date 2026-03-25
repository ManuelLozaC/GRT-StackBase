import api from '@/service/api';
import { computed, reactive } from 'vue';

const state = reactive({
    items: [],
    loading: false,
    loaded: false
});

const enabledKeys = computed(() => {
    return state.items.filter((item) => item.enabled).map((item) => item.key);
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
    const response = await api.patch(`/v1/modules/${moduleKey}`, {
        enabled
    });

    const updatedModule = response.data.datos;
    const index = state.items.findIndex((item) => item.key === moduleKey);

    if (index >= 0) {
        state.items[index] = updatedModule;
    } else {
        state.items.push(updatedModule);
    }

    state.loaded = true;

    return updatedModule;
}

function isModuleEnabled(moduleKey) {
    return enabledKeys.value.includes(moduleKey);
}

function reset() {
    state.items = [];
    state.loading = false;
    state.loaded = false;
}

export const moduleCatalog = {
    state,
    enabledKeys,
    loadModules,
    updateModuleStatus,
    isModuleEnabled,
    reset
};
