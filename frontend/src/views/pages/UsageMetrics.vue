<script setup>
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import { formatDateTime } from '@/core/settings/formatters';
import { useActionFeedback } from '@/core/ui/useActionFeedback';
import api from '@/service/api';
import { computed, onMounted, reactive } from 'vue';

const feedback = useActionFeedback();
const state = reactive({
    loading: false,
    loadError: '',
    summary: null,
    byModule: [],
    byCategory: [],
    recentEvents: [],
    recentSlowRequests: []
});

const hasRecentEvents = computed(() => state.recentEvents.length > 0);

async function loadMetrics() {
    state.loading = true;

    try {
        const response = await api.get('/v1/metrics/overview');
        state.summary = response.data.datos?.summary ?? null;
        state.byModule = response.data.datos?.by_module ?? [];
        state.byCategory = response.data.datos?.by_category ?? [];
        state.recentEvents = response.data.datos?.recent_events ?? [];
        state.recentSlowRequests = response.data.datos?.recent_slow_requests ?? [];
        state.loadError = '';
    } catch (error) {
        state.loadError = feedback.messageFromError(error, 'No se pudieron cargar las metricas del tenant activo.');
        feedback.showError('No se pudieron cargar las metricas', error, state.loadError);
    } finally {
        state.loading = false;
    }
}

onMounted(loadMetrics);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="app-panel-header">
                <div class="app-panel-header-copy">
                    <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Metrics</div>
                    <h1 class="text-3xl font-semibold text-slate-900 mb-3">Usage Metrics</h1>
                    <p class="text-slate-600 max-w-3xl">Base de metricas internas por tenant, modulo y categoria para ver adopcion operativa del core y de los modulos activos.</p>
                </div>
                <div class="app-panel-actions">
                    <Button class="app-button-standard" label="Actualizar" icon="pi pi-refresh" severity="secondary" outlined :loading="state.loading" @click="loadMetrics" />
                </div>
            </div>
        </div>

        <StateSkeleton v-if="state.loading" />

        <template v-else>
            <Message v-if="state.loadError" severity="warn" :closable="false">{{ state.loadError }}</Message>

            <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-500">Eventos 24h</div>
                    <div class="mt-4 text-4xl font-semibold text-slate-900">{{ state.summary?.events_last_24h ?? 0 }}</div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-500">Modulos activos</div>
                    <div class="mt-4 text-4xl font-semibold text-slate-900">{{ state.summary?.active_modules_last_24h ?? 0 }}</div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-500">Categorias activas</div>
                    <div class="mt-4 text-4xl font-semibold text-slate-900">{{ state.summary?.active_categories_last_24h ?? 0 }}</div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-500">Tiempo medio API</div>
                    <div class="mt-4 text-4xl font-semibold text-slate-900">{{ state.summary?.average_response_time_ms ?? 0 }}ms</div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-500">Requests lentos</div>
                    <div class="mt-4 text-4xl font-semibold text-slate-900">{{ state.summary?.slow_requests_last_24h ?? 0 }}</div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900 mb-4">Eventos por modulo</h2>
                    <StateEmpty v-if="!state.byModule.length" title="Sin eventos por modulo" description="Todavia no hay actividad metricable en la ventana actual." icon="pi pi-chart-bar" />
                    <DataTable v-else :value="state.byModule" dataKey="module_key">
                        <Column field="module_key" header="Modulo" style="min-width: 14rem" />
                        <Column field="total" header="Eventos" style="min-width: 8rem" />
                    </DataTable>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900 mb-4">Eventos por categoria</h2>
                    <StateEmpty v-if="!state.byCategory.length" title="Sin categorias activas" description="Todavia no hay categorias con actividad metrica en la ventana actual." icon="pi pi-sitemap" />
                    <DataTable v-else :value="state.byCategory" dataKey="event_category">
                        <Column field="event_category" header="Categoria" style="min-width: 14rem" />
                        <Column field="total" header="Eventos" style="min-width: 8rem" />
                    </DataTable>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Eventos recientes</h2>
                <StateEmpty v-if="!hasRecentEvents" title="Sin eventos recientes" description="Todavia no se registraron eventos metricos dentro del tenant activo." icon="pi pi-wave-pulse" />
                <DataTable v-else :value="state.recentEvents" dataKey="id">
                    <Column field="occurred_at" header="Fecha" style="min-width: 13rem">
                        <template #body="slotProps">{{ formatDateTime(slotProps.data.occurred_at) }}</template>
                    </Column>
                    <Column field="module_key" header="Modulo" style="min-width: 12rem" />
                    <Column field="event_category" header="Categoria" style="min-width: 12rem" />
                    <Column field="event_key" header="Evento" style="min-width: 16rem" />
                    <Column field="request_id" header="Request ID" style="min-width: 14rem" />
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

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Requests lentos recientes</h2>
                <StateEmpty v-if="!state.recentSlowRequests.length" title="Sin requests lentos" description="No se detectaron requests lentos en la ventana operativa actual." icon="pi pi-stopwatch" />
                <DataTable v-else :value="state.recentSlowRequests" dataKey="id">
                    <Column field="occurred_at" header="Fecha" style="min-width: 12rem">
                        <template #body="slotProps">{{ formatDateTime(slotProps.data.occurred_at) }}</template>
                    </Column>
                    <Column field="module_key" header="Modulo" style="min-width: 10rem" />
                    <Column field="method" header="Metodo" style="min-width: 8rem" />
                    <Column field="path" header="Path" style="min-width: 18rem" />
                    <Column field="status" header="Estado" style="min-width: 8rem" />
                    <Column field="duration_ms" header="Duracion" style="min-width: 8rem" />
                </DataTable>
            </div>
        </template>
    </div>
</template>
