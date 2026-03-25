<script setup>
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import { formatDateTime } from '@/core/settings/formatters';
import api from '@/service/api';
import { computed, onMounted, reactive } from 'vue';

const state = reactive({
    loading: false,
    items: []
});

const hasItems = computed(() => state.items.length > 0);

async function loadLogs() {
    state.loading = true;

    try {
        const response = await api.get('/v1/error-logs');
        state.items = response.data.datos ?? [];
    } finally {
        state.loading = false;
    }
}

onMounted(loadLogs);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Errors</div>
            <h1 class="text-3xl font-semibold text-slate-900 mb-3">Error Logs</h1>
            <p class="text-slate-600 max-w-3xl">Registro tecnico de excepciones no controladas para soporte operativo del tenant activo y correlacion con `request_id`.</p>
        </div>

        <StateSkeleton v-if="state.loading" />

        <div v-else class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <StateEmpty v-if="!hasItems" title="Sin errores registrados" description="Todavia no existen errores tecnicos no controlados dentro del tenant activo." icon="pi pi-times-circle" />

            <DataTable v-else :value="state.items" dataKey="id">
                <Column field="occurred_at" header="Fecha" style="min-width: 13rem">
                    <template #body="slotProps">{{ formatDateTime(slotProps.data.occurred_at) }}</template>
                </Column>
                <Column field="error_code" header="Codigo" style="min-width: 12rem" />
                <Column field="error_class" header="Clase" style="min-width: 16rem" />
                <Column field="message" header="Mensaje" style="min-width: 20rem" />
                <Column field="request_id" header="Request ID" style="min-width: 14rem" />
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
</template>
