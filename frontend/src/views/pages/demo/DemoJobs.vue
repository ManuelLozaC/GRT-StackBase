<script setup>
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';
import api from '@/service/api';
import { computed, onBeforeUnmount, onMounted, reactive } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const form = reactive({
    message: 'Generar resumen operativo de demo',
    mode: 'queued',
    should_fail: false
});

const state = reactive({
    loading: false,
    submitting: false,
    retryingUuid: null,
    jobs: [],
    workerHint: 'Docker local: docker compose up -d worker scheduler. Manual: php artisan queue:work --queue=demo',
    queueRuntime: null
});

let pollingHandle = null;

async function loadJobs() {
    state.loading = true;

    try {
        const response = await api.get('/v1/demo/jobs');
        state.jobs = response.data.datos ?? [];
        state.queueRuntime = response.data.meta?.queue_runtime ?? null;
    } finally {
        state.loading = false;
    }
}

async function dispatchJob() {
    state.submitting = true;

    try {
        const response = await api.post('/v1/demo/jobs', {
            message: form.message,
            mode: form.mode,
            should_fail: form.should_fail
        });

        if (response.data.meta?.worker_hint) {
            state.workerHint = response.data.meta.worker_hint;
        }

        if (response.data.meta?.queue_runtime) {
            state.queueRuntime = response.data.meta.queue_runtime;
        }

        toast.add({
            severity: response.data.datos.status === 'failed' ? 'warn' : 'success',
            summary: form.mode === 'queued' ? 'Job enviado' : 'Job ejecutado',
            detail: response.data.mensaje,
            life: 3000
        });

        await loadJobs();
    } finally {
        state.submitting = false;
    }
}

async function retryJob(job) {
    state.retryingUuid = job.uuid;

    try {
        const response = await api.post(`/v1/demo/jobs/${job.uuid}/retry`);

        if (response.data.meta?.worker_hint) {
            state.workerHint = response.data.meta.worker_hint;
        }

        if (response.data.meta?.queue_runtime) {
            state.queueRuntime = response.data.meta.queue_runtime;
        }

        toast.add({
            severity: 'success',
            summary: 'Job reenviado',
            detail: response.data.mensaje,
            life: 3000
        });

        await loadJobs();
    } finally {
        state.retryingUuid = null;
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

function resolveSeverity(status) {
    if (status === 'completed') {
        return 'success';
    }

    if (status === 'processing') {
        return 'info';
    }

    if (status === 'failed') {
        return 'danger';
    }

    return 'warning';
}

const hasAsyncWorkInProgress = computed(() => {
    const hasPendingRuns = state.jobs.some((job) => ['pending', 'processing'].includes(job.status));
    const pendingQueue = (state.queueRuntime?.pending_total ?? 0) > 0;

    return hasPendingRuns || pendingQueue;
});

function startPolling() {
    if (pollingHandle !== null) {
        return;
    }

    pollingHandle = window.setInterval(() => {
        if (hasAsyncWorkInProgress.value) {
            loadJobs();
        }
    }, 5000);
}

function stopPolling() {
    if (pollingHandle !== null) {
        window.clearInterval(pollingHandle);
        pollingHandle = null;
    }
}

onMounted(async () => {
    await loadJobs();
    startPolling();
});

onBeforeUnmount(stopPolling);
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / Jobs" class="w-fit" />
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h2 class="m-0">Demo funcional de jobs</h2>
                        <p class="m-0 text-color-secondary">Valida dispatch en cola, ejecucion inmediata para pruebas locales, estados y logs basicos por tenant.</p>
                    </div>
                    <div class="demo-job-summary">
                        <div>
                            <strong>{{ state.jobs.length }}</strong>
                            <span>ejecuciones</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="card flex flex-col gap-4">
                <div>
                    <h3 class="m-0 mb-2">Lanzar job demo</h3>
                    <p class="m-0 text-sm text-color-secondary">El job transforma texto y puede simular fallos para probar reintentos, cola real y observabilidad operativa en local.</p>
                </div>

                <textarea v-model="form.message" rows="5" class="demo-textarea" placeholder="Texto a procesar"></textarea>

                <label class="demo-field">
                    <span>Modo de ejecucion</span>
                    <select v-model="form.mode" class="demo-select">
                        <option value="queued">En cola</option>
                        <option value="immediate">Inmediato</option>
                    </select>
                </label>

                <label class="demo-checkbox">
                    <input v-model="form.should_fail" type="checkbox" />
                    <span>Forzar fallo controlado</span>
                </label>

                <button class="demo-primary-button" :disabled="state.submitting" @click="dispatchJob">
                    {{ state.submitting ? 'Procesando...' : 'Ejecutar demo job' }}
                </button>

                <div class="demo-hint-box">
                    <strong>Tip worker</strong>
                    <span>{{ state.workerHint }}</span>
                </div>

                <div v-if="state.queueRuntime" class="demo-hint-box">
                    <strong>Estado de cola</strong>
                    <span>Conexion: {{ state.queueRuntime.connection }}</span>
                    <span>Pendientes: {{ state.queueRuntime.pending_total ?? '-' }}</span>
                    <span>Fallidos: {{ state.queueRuntime.failed_total ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="card">
                <div class="app-panel-header">
                    <div class="app-panel-header-copy">
                        <h3 class="m-0">Historial de ejecuciones</h3>
                        <p class="m-0 text-sm text-color-secondary">Los jobs quedan registrados con payload, resultado, intentos y errores dentro del tenant activo.</p>
                    </div>
                    <div class="app-panel-actions">
                        <Tag v-if="hasAsyncWorkInProgress" severity="info" value="Auto-poll activo" />
                        <button class="demo-secondary-button app-button-standard" :disabled="state.loading" @click="loadJobs">
                            {{ state.loading ? 'Actualizando...' : 'Actualizar' }}
                        </button>
                    </div>
                </div>

                <div v-if="state.jobs.length === 0" class="demo-empty-state">Todavia no hay ejecuciones. Lanza un job en cola o inmediato para validar el flujo.</div>

                <div v-else class="demo-job-list">
                    <article v-for="job in state.jobs" :key="job.uuid" class="demo-job-card">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ job.job_key }}</div>
                                <div class="text-sm text-color-secondary">{{ job.queue }} - solicitado por {{ job.requested_by || 'sistema' }}</div>
                            </div>
                            <Tag :severity="resolveSeverity(job.status)" :value="job.status" />
                        </div>

                        <div class="demo-job-meta">
                            <span><strong>Intentos:</strong> {{ job.attempts }}</span>
                            <span><strong>Maximo:</strong> {{ job.max_tries }}</span>
                            <span><strong>Backoff:</strong> {{ job.backoff_schedule.join(', ') }} s</span>
                            <span><strong>Despachado:</strong> {{ formatDate(job.dispatched_at) }}</span>
                            <span><strong>Finalizado:</strong> {{ formatDate(job.finished_at) }}</span>
                        </div>

                        <div class="demo-job-meta">
                            <span><strong>Tenant:</strong> {{ job.organizacion_id || '-' }}</span>
                            <span><strong>Empresa:</strong> {{ job.empresa_id || '-' }}</span>
                            <span><strong>Actor:</strong> {{ job.requested_by || 'sistema' }} (#{{ job.requested_by_id || '-' }})</span>
                        </div>

                        <div class="demo-payload-box">
                            <div>
                                <strong>Payload</strong>
                                <pre>{{ JSON.stringify(job.requested_payload, null, 2) }}</pre>
                            </div>
                            <div v-if="job.result_payload">
                                <strong>Resultado</strong>
                                <pre>{{ JSON.stringify(job.result_payload, null, 2) }}</pre>
                            </div>
                            <div v-if="job.error_message" class="demo-error-box">
                                <strong>Error</strong>
                                <pre>{{ job.error_message }}</pre>
                            </div>
                        </div>

                        <div class="demo-job-actions">
                            <button v-if="job.can_retry" class="demo-secondary-button" :disabled="state.retryingUuid === job.uuid" @click="retryJob(job)">
                                {{ state.retryingUuid === job.uuid ? 'Reintentando...' : 'Reintentar en cola' }}
                            </button>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <DemoPatternGuide
                title="Guia para jobs y background processing"
                :when-to-use="['cuando una accion necesita salir del request principal', 'cuando conviene ofrecer modo queued y modo immediate para pruebas', 'cuando el equipo necesita ver payload, resultado, intentos y diagnostico de cola']"
                :avoid-when="['cuando el trabajo es trivial y solo agrega complejidad moverlo a cola', 'cuando no existe una forma clara de observar estado o errores', 'cuando el usuario necesita respuesta inmediata y el flujo no tolera asincronia']"
                :wiring="['registrar payload solicitado, resultado y error dentro del run', 'exponer worker hints, pending counts y auto-poll cuando aplique', 'mantener historial visible por tenant, empresa y actor']"
                :notes="['esta demo usa el core real de jobs del stack', 'si el flujo crece, combinarlo con async patterns para una UX mas rica y notificaciones al finalizar']"
            />
        </div>
    </div>
</template>

<style scoped>
.demo-job-summary {
    display: flex;
    gap: 1rem;
}

.demo-job-summary > div {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 6rem;
    padding: 0.9rem 1rem;
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--surface-card), var(--surface-ground));
}

.demo-job-summary strong {
    font-size: 1.35rem;
}

.demo-job-summary span {
    font-size: 0.85rem;
    color: var(--text-color-secondary);
}

.demo-textarea,
.demo-select {
    width: 100%;
    border: 1px solid var(--surface-border);
    border-radius: 0.85rem;
    padding: 0.85rem 1rem;
    background: var(--surface-card);
    color: var(--text-color);
}

.demo-field,
.demo-checkbox {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.demo-checkbox {
    flex-direction: row;
    align-items: center;
}

.demo-primary-button,
.demo-secondary-button {
    border: 0;
    border-radius: 999px;
    padding: 0.75rem 1rem;
    font-weight: 600;
    cursor: pointer;
}

.demo-primary-button {
    background: var(--primary-color);
    color: var(--primary-contrast-color);
}

.demo-secondary-button {
    background: var(--surface-200);
    color: var(--text-color);
}

.demo-primary-button:disabled,
.demo-secondary-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.demo-hint-box,
.demo-empty-state,
.demo-job-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    padding: 1rem;
    background: var(--surface-ground);
}

.demo-hint-box {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.demo-job-list {
    display: grid;
    gap: 1rem;
}

.demo-job-card {
    background: var(--surface-card);
}

.demo-job-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 0.9rem;
    font-size: 0.875rem;
    color: var(--text-color-secondary);
}

.demo-payload-box {
    display: grid;
    gap: 0.75rem;
    margin-top: 1rem;
}

.demo-job-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 1rem;
}

.demo-payload-box pre {
    margin: 0.35rem 0 0;
    padding: 0.85rem;
    border-radius: 0.85rem;
    background: var(--surface-ground);
    overflow: auto;
    font-size: 0.8rem;
}

.demo-error-box pre {
    color: #b42318;
}
</style>
