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
        const response = await api.get('/v1/security/logs');
        state.items = response.data.datos ?? [];
    } finally {
        state.loading = false;
    }
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
