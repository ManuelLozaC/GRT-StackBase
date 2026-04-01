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
        severity: '',
        event_key: '',
        request_id: ''
    }
});

const hasItems = computed(() => state.items.length > 0);
const severityOptions = [
    { label: 'Info', value: 'info' },
    { label: 'Warning', value: 'warning' },
    { label: 'Danger', value: 'danger' }
];
const eventOptions = computed(() =>
    [...new Set(state.items.map((item) => item.event_key).filter(Boolean))].map((value) => ({
        label: value,
        value
    }))
);

async function loadLogs() {
    state.loading = true;

    try {
        const response = await api.get('/v1/security/logs', {
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
        severity: '',
        event_key: '',
        request_id: ''
    };
    loadLogs();
}

function severityFor(level) {
    return (
        {
            info: 'info',
            warning: 'warning',
            danger: 'danger'
        }[level] ?? 'contrast'
    );
}

onMounted(loadLogs);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Security</div>
            <h1 class="text-3xl font-semibold text-slate-900 mb-3">Security Logs</h1>
            <p class="text-slate-600 max-w-3xl">Esta vista centraliza eventos operativos sensibles para soporte, troubleshooting y endurecimiento progresivo del core.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="app-panel-header">
                <div class="app-panel-header-copy">
                    <h2 class="text-xl font-semibold text-slate-900 m-0">Filtros operativos</h2>
                    <p class="text-sm text-slate-600 m-0">Busca por evento, request ID, actor o IP para investigar actividad sensible dentro de la empresa activa.</p>
                </div>
                <div class="app-panel-actions">
                    <Button class="app-button-standard" label="Actualizar" icon="pi pi-refresh" severity="secondary" outlined :loading="state.loading" @click="loadLogs" />
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 lg:col-span-5">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Busqueda global</label>
                    <IconField>
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="state.filters.q" class="w-full" placeholder="Evento, resumen, request ID, actor o IP" @keyup.enter="applyFilters" />
                    </IconField>
                </div>
                <div class="col-span-12 md:col-span-4 lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Severidad</label>
                    <Select v-model="state.filters.severity" :options="severityOptions" optionLabel="label" optionValue="value" showClear class="w-full" />
                </div>
                <div class="col-span-12 md:col-span-4 lg:col-span-3">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Evento</label>
                    <Select v-model="state.filters.event_key" :options="eventOptions" optionLabel="label" optionValue="value" showClear class="w-full" />
                </div>
                <div class="col-span-12 md:col-span-4 lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Request ID</label>
                    <InputText v-model="state.filters.request_id" class="w-full" placeholder="req-..." />
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <Button class="app-button-standard" label="Aplicar filtros" icon="pi pi-filter" @click="applyFilters" />
                <Button class="app-button-standard" label="Limpiar" severity="secondary" outlined @click="clearFilters" />
            </div>
        </div>

        <StateSkeleton v-if="state.loading" />

        <div v-else class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <StateEmpty v-if="!hasItems" title="Sin eventos de seguridad" description="Todavia no se registraron eventos de seguridad dentro del tenant activo." icon="pi pi-shield" />

            <div v-else class="overflow-x-auto">
                <DataTable :value="state.items" dataKey="id">
                    <Column field="occurred_at" header="Fecha" style="min-width: 13rem">
                        <template #body="slotProps">{{ formatDateTime(slotProps.data.occurred_at) }}</template>
                    </Column>
                    <Column field="event_key" header="Evento" style="min-width: 14rem" />
                    <Column field="severity" header="Severidad" style="min-width: 10rem">
                        <template #body="slotProps">
                            <Tag :severity="severityFor(slotProps.data.severity)" :value="slotProps.data.severity" />
                        </template>
                    </Column>
                    <Column field="summary" header="Resumen" style="min-width: 22rem" />
                    <Column field="request_id" header="Request ID" style="min-width: 16rem" />
                    <Column field="ip_address" header="IP" style="min-width: 10rem" />
                    <Column header="Actor" style="min-width: 14rem">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.actor" class="flex flex-col gap-1">
                                <strong>{{ slotProps.data.actor.name }}</strong>
                                <small class="text-color-secondary">{{ slotProps.data.actor.email }}</small>
                            </div>
                            <span v-else class="text-color-secondary">Sistema</span>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>
