<script setup>
import api from '@/service/api';
import { computed, onMounted, reactive, ref } from 'vue';

const state = reactive({
    loading: false,
    logs: []
});

const search = ref('');

const filteredLogs = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) {
        return state.logs;
    }

    return state.logs.filter((log) => {
        return [log.event_key, log.entity_type, log.entity_key, log.summary, log.actor?.name, log.actor?.email, log.source_module].filter(Boolean).some((value) => String(value).toLowerCase().includes(term));
    });
});

async function loadLogs() {
    state.loading = true;

    try {
        const response = await api.get('/v1/demo/audit');
        state.logs = response.data.datos ?? [];
    } finally {
        state.loading = false;
    }
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('es-BO', {
        dateStyle: 'medium',
        timeStyle: 'short'
    }).format(new Date(value));
}

function prettyContext(context) {
    return JSON.stringify(context ?? {}, null, 2);
}

onMounted(loadLogs);
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / Audit" class="w-fit" />
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h2 class="m-0">Demo funcional de auditoria</h2>
                        <p class="m-0 text-color-secondary">Muestra actividad transversal del core: modulos, archivos y jobs con actor, fecha, entidad y contexto.</p>
                    </div>
                    <div class="demo-audit-summary">
                        <div>
                            <strong>{{ filteredLogs.length }}</strong>
                            <span>eventos visibles</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="m-0">Actividad reciente</h3>
                        <p class="m-0 text-sm text-color-secondary">Usa las demos de archivos, jobs o la administracion de modulos y vuelve aqui para inspeccionar trazabilidad.</p>
                    </div>
                    <div class="demo-audit-toolbar">
                        <input v-model="search" type="text" class="demo-search" placeholder="Buscar evento, actor o entidad" />
                        <button class="demo-secondary-button" :disabled="state.loading" @click="loadLogs">
                            {{ state.loading ? 'Actualizando...' : 'Actualizar' }}
                        </button>
                    </div>
                </div>

                <div v-if="filteredLogs.length === 0" class="demo-empty-state">No hay eventos que coincidan con el filtro actual.</div>

                <div v-else class="demo-audit-list">
                    <article v-for="log in filteredLogs" :key="log.id" class="demo-audit-card">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ log.summary || log.event_key }}</div>
                                <div class="text-sm text-color-secondary">{{ log.event_key }} · {{ formatDate(log.occurred_at) }}</div>
                            </div>
                            <Tag severity="info" :value="log.source_module || 'core'" />
                        </div>

                        <div class="demo-audit-meta">
                            <span><strong>Actor:</strong> {{ log.actor?.name || 'sistema' }}</span>
                            <span><strong>Email:</strong> {{ log.actor?.email || '-' }}</span>
                            <span><strong>Entidad:</strong> {{ log.entity_type || '-' }}</span>
                            <span><strong>Clave:</strong> {{ log.entity_key || '-' }}</span>
                        </div>

                        <pre class="demo-context-box">{{ prettyContext(log.context) }}</pre>
                    </article>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.demo-audit-summary {
    display: flex;
    gap: 1rem;
}

.demo-audit-summary > div {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 7rem;
    padding: 0.9rem 1rem;
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--surface-card), var(--surface-ground));
}

.demo-audit-summary strong {
    font-size: 1.35rem;
}

.demo-audit-summary span {
    font-size: 0.85rem;
    color: var(--text-color-secondary);
}

.demo-audit-toolbar {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.demo-search {
    min-width: 18rem;
    border: 1px solid var(--surface-border);
    border-radius: 999px;
    padding: 0.75rem 1rem;
    background: var(--surface-card);
    color: var(--text-color);
}

.demo-secondary-button {
    border: 0;
    border-radius: 999px;
    padding: 0.75rem 1rem;
    font-weight: 600;
    cursor: pointer;
    background: var(--surface-200);
    color: var(--text-color);
}

.demo-secondary-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.demo-empty-state,
.demo-audit-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    padding: 1rem;
    background: var(--surface-ground);
}

.demo-audit-list {
    display: grid;
    gap: 1rem;
}

.demo-audit-card {
    background: var(--surface-card);
}

.demo-audit-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 0.9rem;
    font-size: 0.875rem;
    color: var(--text-color-secondary);
}

.demo-context-box {
    margin: 1rem 0 0;
    padding: 0.9rem;
    border-radius: 0.85rem;
    background: var(--surface-ground);
    overflow: auto;
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .demo-search {
        min-width: 100%;
    }
}
</style>
