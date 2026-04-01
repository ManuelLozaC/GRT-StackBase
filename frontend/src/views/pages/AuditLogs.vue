<script setup>
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import { formatDateTime } from '@/core/settings/formatters';
import api from '@/service/api';
import { computed, onMounted, reactive } from 'vue';

const state = reactive({
    loading: false,
    items: [],
    filters: {
        q: '',
        event_key: '',
        entity_type: '',
        source_module: ''
    }
});

const hasItems = computed(() => state.items.length > 0);
const eventOptions = computed(() => uniqueOptions(state.items.map((item) => item.event_key)));
const entityTypeOptions = computed(() => uniqueOptions(state.items.map((item) => item.entity_type)));
const moduleOptions = computed(() => uniqueOptions(state.items.map((item) => item.source_module)));

function uniqueOptions(values) {
    return [...new Set(values.filter(Boolean))].map((value) => ({
        label: value,
        value
    }));
}

async function loadLogs() {
    state.loading = true;

    try {
        const response = await api.get('/v1/audit/logs', {
            params: {
                ...Object.fromEntries(Object.entries(state.filters).filter(([, value]) => value))
            }
        });
        state.items = response.data.datos ?? [];
    } finally {
        state.loading = false;
    }
}

function applyFilters() {
    loadLogs();
}

function clearFilters() {
    state.filters = {
        q: '',
        event_key: '',
        entity_type: '',
        source_module: ''
    };
    loadLogs();
}

function prettyContext(context) {
    return JSON.stringify(context ?? {}, null, 2);
}

onMounted(loadLogs);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Audit</div>
            <h1 class="text-3xl font-semibold text-slate-900 mb-3">Audit Logs</h1>
            <p class="text-slate-600 max-w-3xl">Historial transversal de acciones relevantes del sistema, con actor, entidad, modulo y contexto serializado para soporte operativo.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="app-panel-header">
                <div class="app-panel-header-copy">
                    <h2 class="text-xl font-semibold text-slate-900 m-0">Filtros de investigacion</h2>
                    <p class="text-sm text-slate-600 m-0">Busca por evento, actor, entidad o modulo para reconstruir que paso dentro de la empresa activa.</p>
                </div>
                <div class="app-panel-actions">
                    <Button class="app-button-standard" label="Actualizar" icon="pi pi-refresh" severity="secondary" outlined :loading="state.loading" @click="loadLogs" />
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 lg:col-span-6">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Busqueda global</label>
                    <IconField>
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="state.filters.q" class="w-full" placeholder="Evento, actor, email, entidad o modulo" @keyup.enter="applyFilters" />
                    </IconField>
                </div>
                <div class="col-span-12 md:col-span-4 lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Evento</label>
                    <Select v-model="state.filters.event_key" :options="eventOptions" optionLabel="label" optionValue="value" showClear class="w-full" />
                </div>
                <div class="col-span-12 md:col-span-4 lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Entidad</label>
                    <Select v-model="state.filters.entity_type" :options="entityTypeOptions" optionLabel="label" optionValue="value" showClear class="w-full" />
                </div>
                <div class="col-span-12 md:col-span-4 lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Modulo</label>
                    <Select v-model="state.filters.source_module" :options="moduleOptions" optionLabel="label" optionValue="value" showClear class="w-full" />
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <Button class="app-button-standard" label="Aplicar filtros" icon="pi pi-filter" @click="applyFilters" />
                <Button class="app-button-standard" label="Limpiar" severity="secondary" outlined @click="clearFilters" />
            </div>
        </div>

        <StateSkeleton v-if="state.loading" />

        <div v-else class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="app-panel-header mb-4">
                <div class="app-panel-header-copy">
                    <h2 class="text-xl font-semibold text-slate-900 m-0">Resultados</h2>
                    <p class="text-sm text-slate-600 m-0">Cada evento deja actor, entidad, modulo y contexto serializado para reconstruir lo ocurrido.</p>
                </div>
                <div class="app-panel-actions">
                    <Tag severity="contrast" :value="`${state.items.length} evento${state.items.length === 1 ? '' : 's'}`" />
                </div>
            </div>

            <StateEmpty v-if="!hasItems" title="Sin trazas de auditoria" description="No hay eventos de auditoria para la combinacion actual de filtros dentro de la empresa activa." icon="pi pi-history" />

            <div v-else class="space-y-4">
                <article v-for="item in state.items" :key="item.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">{{ item.summary || item.event_key }}</div>
                            <div class="text-sm text-slate-500">{{ item.event_key }} - {{ formatDateTime(item.occurred_at) }}</div>
                        </div>
                        <Tag severity="info" :value="item.source_module || 'core'" />
                    </div>

                    <div class="mt-3 flex flex-wrap gap-4 text-sm text-slate-600">
                        <span><strong>Actor:</strong> {{ item.actor?.name || 'Sistema' }}</span>
                        <span><strong>Email:</strong> {{ item.actor?.email || '-' }}</span>
                        <span><strong>Entidad:</strong> {{ item.entity_type || '-' }}</span>
                        <span><strong>Clave:</strong> {{ item.entity_key || '-' }}</span>
                    </div>

                    <pre class="mt-4 overflow-auto rounded-2xl bg-white p-4 text-xs text-slate-700">{{ prettyContext(item.context) }}</pre>
                </article>
            </div>
        </div>
    </div>
</template>
