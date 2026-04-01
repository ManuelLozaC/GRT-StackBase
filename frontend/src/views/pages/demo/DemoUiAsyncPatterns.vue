<script setup>
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';
import { computed, onBeforeUnmount, reactive } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const state = reactive({
    saving: false,
    retrying: false,
    pollingActive: false,
    progressRunning: false,
    progress: 0,
    lastStatus: 'Sin ejecuciones recientes.',
    pollTicks: 0
});

let pollingTimer = null;
let progressTimer = null;

const statusTag = computed(() => {
    if (state.saving || state.retrying || state.progressRunning) {
        return { value: 'En proceso', severity: 'warn' };
    }

    if (state.pollingActive) {
        return { value: 'Monitoreando', severity: 'info' };
    }

    return { value: 'Idle', severity: 'secondary' };
});

function simulateSave() {
    state.saving = true;
    state.lastStatus = 'Guardando cambios demo...';

    window.setTimeout(() => {
        state.saving = false;
        state.lastStatus = 'Guardado completado correctamente.';
        toast.add({
            severity: 'success',
            summary: 'Submit con loading',
            detail: 'Se completo el patron de guardado asincrono.',
            life: 2600
        });
    }, 1400);
}

function simulateRetry() {
    state.retrying = true;
    state.lastStatus = 'Primer intento fallido. Ejecutando reintento visible...';

    toast.add({
        severity: 'warn',
        summary: 'Fallo inicial',
        detail: 'Mostrando un warning antes del reintento controlado.',
        life: 2400
    });

    window.setTimeout(() => {
        state.retrying = false;
        state.lastStatus = 'Reintento completado con exito.';
        toast.add({
            severity: 'success',
            summary: 'Retry exitoso',
            detail: 'Este patron sirve para colas, importaciones o integraciones externas.',
            life: 2600
        });
    }, 2200);
}

function togglePolling() {
    if (state.pollingActive) {
        window.clearInterval(pollingTimer);
        pollingTimer = null;
        state.pollingActive = false;
        state.lastStatus = `Polling detenido tras ${state.pollTicks} ciclos.`;
        return;
    }

    state.pollTicks = 0;
    state.pollingActive = true;
    state.lastStatus = 'Polling activo consultando estado remoto...';

    pollingTimer = window.setInterval(() => {
        state.pollTicks += 1;
        state.lastStatus = `Polling activo. Ultimo refresh: ciclo ${state.pollTicks}.`;
    }, 1800);
}

function runProgressFlow() {
    if (state.progressRunning) {
        return;
    }

    state.progressRunning = true;
    state.progress = 0;
    state.lastStatus = 'Proceso con progreso visible iniciado.';

    progressTimer = window.setInterval(() => {
        state.progress = Math.min(state.progress + 10, 100);

        if (state.progress >= 100) {
            window.clearInterval(progressTimer);
            progressTimer = null;
            state.progressRunning = false;
            state.lastStatus = 'Proceso completado al 100%.';
            toast.add({
                severity: 'success',
                summary: 'Progreso completado',
                detail: 'Este patron funciona bien para imports, exports o tareas pesadas.',
                life: 2600
            });
        }
    }, 350);
}

onBeforeUnmount(() => {
    if (pollingTimer) {
        window.clearInterval(pollingTimer);
    }

    if (progressTimer) {
        window.clearInterval(progressTimer);
    }
});
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / UI Async Patterns" class="w-fit" />
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <h2 class="m-0">Patrones async y estados largos</h2>
                        <p class="m-0 text-color-secondary">Esta demo muestra wiring visual para submit con loading, reintentos, polling, progreso y estados operativos visibles para acciones asincronas.</p>
                    </div>
                    <Tag :severity="statusTag.severity" :value="statusTag.value" />
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-7">
            <div class="card flex flex-col gap-4">
                <div>
                    <h3 class="m-0 mb-2">Acciones demo</h3>
                    <p class="m-0 text-sm text-color-secondary">Cada accion representa un patron que luego puede copiarse en modulos de negocio o integraciones.</p>
                </div>

                <div class="async-button-grid">
                    <Button :label="state.saving ? 'Guardando...' : 'Submit con loading'" :loading="state.saving" @click="simulateSave" />
                    <Button :label="state.retrying ? 'Reintentando...' : 'Fallo + retry visible'" severity="warn" :loading="state.retrying" @click="simulateRetry" />
                    <Button :label="state.pollingActive ? 'Detener polling' : 'Iniciar polling'" severity="info" outlined @click="togglePolling" />
                    <Button :label="state.progressRunning ? 'Procesando...' : 'Flujo con progreso'" severity="contrast" :disabled="state.progressRunning" @click="runProgressFlow" />
                </div>

                <div class="async-status-card">
                    <div class="font-semibold mb-2">Estado operativo</div>
                    <p class="m-0 text-color-secondary">{{ state.lastStatus }}</p>
                </div>

                <div class="async-progress-block">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <strong>Progreso visible</strong>
                        <span>{{ state.progress }}%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" :style="{ width: `${state.progress}%` }"></div>
                    </div>
                </div>

                <DemoPatternGuide
                    title="Guia para flujos asincronos"
                    :when-to-use="['cuando guardar, importar o sincronizar no responde de inmediato', 'cuando el usuario necesita visibilidad del estado de un proceso', 'cuando hay reintentos o polling que deben ser explicitos']"
                    :avoid-when="[
                        'cuando la accion termina casi instantaneamente y agregar progreso solo distrae',
                        'cuando el polling oculta la falta de un endpoint de estado mas claro',
                        'cuando el retry es automatico pero no se explica su impacto'
                    ]"
                    :wiring="[
                        'exponer estado local claro: saving, retrying, pollingActive y progress',
                        'emitir feedback visual y textual en cada transicion importante con useActionFeedback cuando aplique',
                        'limpiar timers y side effects al desmontar la pantalla'
                    ]"
                    :notes="['estos patrones son ideales para jobs, exports, imports y conectores externos', 'si el proceso tiene actor o tenant, reflejarlo tambien en el mensaje visible']"
                />
            </div>
        </div>

        <div class="col-span-12 xl:col-span-5">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Cuando usar cada patron</h3>
                    <p class="m-0 text-sm text-color-secondary">Resumen rapido para no reinventar UX cada vez.</p>
                </div>

                <div class="async-tip-list">
                    <article class="async-tip-card">
                        <div class="font-semibold">Submit con loading</div>
                        <p class="m-0 text-sm text-color-secondary">Para formularios cortos y acciones sin navegacion inmediata.</p>
                    </article>
                    <article class="async-tip-card">
                        <div class="font-semibold">Retry visible</div>
                        <p class="m-0 text-sm text-color-secondary">Para integraciones, webhooks, importaciones o procesos no deterministas.</p>
                    </article>
                    <article class="async-tip-card">
                        <div class="font-semibold">Polling</div>
                        <p class="m-0 text-sm text-color-secondary">Para jobs, exportaciones y procesos que cambian de estado en segundo plano.</p>
                    </article>
                    <article class="async-tip-card">
                        <div class="font-semibold">Progreso</div>
                        <p class="m-0 text-sm text-color-secondary">Para imports, migraciones o tareas largas donde el usuario necesita contexto.</p>
                    </article>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.async-button-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
    gap: 0.85rem;
}

.async-status-card,
.async-tip-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-ground);
    padding: 1rem;
}

.async-tip-list {
    display: grid;
    gap: 0.85rem;
}

.async-progress-block {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-card);
    padding: 1rem;
}

.progress-track {
    width: 100%;
    height: 0.85rem;
    overflow: hidden;
    border-radius: 999px;
    background: var(--surface-200);
}

.progress-fill {
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--primary-color), #14b8a6);
    transition: width 0.25s ease;
}
</style>
