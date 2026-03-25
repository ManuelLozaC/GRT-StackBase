<script setup>
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import { formatDateTime } from '@/core/settings/formatters';
import api from '@/service/api';
import { computed, onMounted, reactive } from 'vue';

const state = reactive({
    loading: false,
    summary: null,
    recentFailedJobs: [],
    recentFailedTransfers: [],
    recentSecurityEvents: [],
    generatedAt: null
});

const summaryCards = computed(() => {
    if (!state.summary) {
        return [];
    }

    return [
        {
            title: 'Seguridad',
            value: state.summary.security?.events_last_24h ?? 0,
            caption: `${state.summary.security?.warnings_last_24h ?? 0} warning(s) en 24h`
        },
        {
            title: 'Jobs',
            value: state.summary.jobs?.pending ?? 0,
            caption: `${state.summary.jobs?.failed_last_24h ?? 0} fallidos en 24h`
        },
        {
            title: 'Transfers',
            value: state.summary.transfers?.processing ?? 0,
            caption: `${state.summary.transfers?.failed_last_24h ?? 0} con error en 24h`
        },
        {
            title: 'Notificaciones',
            value: state.summary.notifications?.unread ?? 0,
            caption: `${state.summary.notifications?.sent_last_24h ?? 0} creadas en 24h`
        },
        {
            title: 'Archivos',
            value: state.summary.files?.total ?? 0,
            caption: `${state.summary.files?.downloads_last_24h ?? 0} descargas en 24h`
        },
        {
            title: 'Auditoria',
            value: state.summary.audit_events_last_24h ?? 0,
            caption: 'eventos registrados en 24h'
        }
    ];
});

async function loadOverview() {
    state.loading = true;

    try {
        const response = await api.get('/v1/operations/overview');
        const data = response.data.datos ?? {};

        state.summary = data.summary ?? null;
        state.recentFailedJobs = data.recent_failed_jobs ?? [];
        state.recentFailedTransfers = data.recent_failed_transfers ?? [];
        state.recentSecurityEvents = data.recent_security_events ?? [];
        state.generatedAt = response.data.meta?.generated_at ?? null;
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

onMounted(loadOverview);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Operations</div>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-slate-900 mb-3">Operations Overview</h1>
                    <p class="text-slate-600 max-w-3xl">Vista ejecutiva del tenant activo para seguir seguridad, jobs, transferencias, auditoria, archivos y notificaciones sin salir del shell administrativo.</p>
                </div>
                <Tag severity="contrast" :value="state.generatedAt ? `Actualizado ${formatDateTime(state.generatedAt)}` : 'Sin timestamp'" />
            </div>
        </div>

        <StateSkeleton v-if="state.loading" />

        <template v-else>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="card in summaryCards" :key="card.title" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-500">{{ card.title }}</div>
                    <div class="mt-4 text-4xl font-semibold text-slate-900">{{ card.value }}</div>
                    <p class="mt-3 text-sm text-slate-600">{{ card.caption }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Jobs con error</h2>
                            <p class="text-sm text-slate-500">Ultimos jobs fallidos dentro del tenant activo.</p>
                        </div>
                        <Tag severity="danger" :value="`${state.recentFailedJobs.length} item(s)`" />
                    </div>

                    <StateEmpty v-if="!state.recentFailedJobs.length" title="Sin jobs fallidos recientes" description="Todavia no hay jobs fallidos en la ventana operativa actual." icon="pi pi-server" />

                    <div v-else class="overflow-x-auto">
                        <DataTable :value="state.recentFailedJobs" dataKey="id">
                            <Column field="job_key" header="Job" style="min-width: 12rem" />
                            <Column field="queue" header="Queue" style="min-width: 8rem" />
                            <Column field="attempts" header="Intentos" style="min-width: 7rem" />
                            <Column field="error_message" header="Error" style="min-width: 16rem" />
                            <Column field="failed_at" header="Fecha" style="min-width: 12rem">
                                <template #body="slotProps">{{ formatDateTime(slotProps.data.failed_at) }}</template>
                            </Column>
                        </DataTable>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Transfers con error</h2>
                            <p class="text-sm text-slate-500">Exportaciones e importaciones que requieren seguimiento.</p>
                        </div>
                        <Tag severity="warning" :value="`${state.recentFailedTransfers.length} item(s)`" />
                    </div>

                    <StateEmpty v-if="!state.recentFailedTransfers.length" title="Sin transfers problematicos" description="No se detectaron transferencias fallidas o con errores parciales en la ventana actual." icon="pi pi-sync" />

                    <div v-else class="overflow-x-auto">
                        <DataTable :value="state.recentFailedTransfers" dataKey="id">
                            <Column field="resource_key" header="Recurso" style="min-width: 10rem" />
                            <Column field="type" header="Tipo" style="min-width: 7rem" />
                            <Column field="status" header="Estado" style="min-width: 9rem" />
                            <Column field="records_failed" header="Fallidos" style="min-width: 7rem" />
                            <Column field="error_summary" header="Resumen" style="min-width: 16rem" />
                            <Column field="finished_at" header="Fecha" style="min-width: 12rem">
                                <template #body="slotProps">{{ formatDateTime(slotProps.data.finished_at) }}</template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Eventos de seguridad recientes</h2>
                        <p class="text-sm text-slate-500">Trazabilidad operativa sensible con request ID y actor asociado.</p>
                    </div>
                    <Tag severity="info" :value="`${state.recentSecurityEvents.length} item(s)`" />
                </div>

                <StateEmpty v-if="!state.recentSecurityEvents.length" title="Sin eventos de seguridad" description="Aun no hay eventos de seguridad recientes dentro del tenant activo." icon="pi pi-shield" />

                <div v-else class="overflow-x-auto">
                    <DataTable :value="state.recentSecurityEvents" dataKey="id">
                        <Column field="occurred_at" header="Fecha" style="min-width: 12rem">
                            <template #body="slotProps">{{ formatDateTime(slotProps.data.occurred_at) }}</template>
                        </Column>
                        <Column field="event_key" header="Evento" style="min-width: 14rem" />
                        <Column field="severity" header="Severidad" style="min-width: 8rem">
                            <template #body="slotProps">
                                <Tag :severity="severityFor(slotProps.data.severity)" :value="slotProps.data.severity" />
                            </template>
                        </Column>
                        <Column field="summary" header="Resumen" style="min-width: 16rem" />
                        <Column field="request_id" header="Request ID" style="min-width: 15rem" />
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
    </div>
</template>
