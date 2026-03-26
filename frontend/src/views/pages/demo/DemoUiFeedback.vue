<script setup>
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import { reactive } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const confirm = useConfirm();

const state = reactive({
    modalVisible: false,
    drawerVisible: false,
    loading: false,
    showEmpty: false,
    lastAction: 'Aun no se ejecuto ninguna accion de feedback.'
});

function showToast(severity) {
    const messages = {
        success: ['Proceso completado', 'El flujo termino correctamente y sirve como referencia de exito.'],
        info: ['Informacion operativa', 'Este patron es util para guiar al usuario sin interrumpir su flujo.'],
        warn: ['Atencion requerida', 'Muestra un warning visible sin bloquear toda la pantalla.'],
        error: ['Error controlado', 'Sirve para errores recuperables y trazables en el shell.']
    };

    const [summary, detail] = messages[severity];

    toast.add({
        severity,
        summary,
        detail,
        life: 2600
    });
}

function openConfirm() {
    confirm.require({
        header: 'Confirmar accion demo',
        message: 'Esta confirmacion existe para mostrar el patron recomendado antes de una accion sensible.',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Continuar',
        rejectLabel: 'Cancelar',
        accept: () => {
            state.lastAction = 'Aceptaste la accion confirmada.';
            showToast('success');
        },
        reject: () => {
            state.lastAction = 'Cancelaste la accion confirmada.';
            showToast('info');
        }
    });
}

function previewLoading() {
    state.loading = true;

    window.setTimeout(() => {
        state.loading = false;
        state.lastAction = 'Se completo el preview de loading.';
    }, 1500);
}
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / UI Feedback" class="w-fit" />
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <h2 class="m-0">Feedback, alerts y overlays</h2>
                        <p class="m-0 text-color-secondary">Esta demo concentra patrones de toasts, confirmaciones, modals, drawers, banners, loaders y estados vacios para que puedan copiarse tal cual en otros modulos.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Tag value="Toasts" severity="success" />
                        <Tag value="Dialogs" severity="info" />
                        <Tag value="States" severity="warn" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-5">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Acciones de feedback</h3>
                    <p class="m-0 text-sm text-color-secondary">Botoneria base para disparar patrones visuales del producto.</p>
                </div>

                <div class="feedback-button-grid">
                    <Button label="Toast success" severity="success" @click="showToast('success')" />
                    <Button label="Toast info" severity="info" @click="showToast('info')" />
                    <Button label="Toast warn" severity="warn" @click="showToast('warn')" />
                    <Button label="Toast error" severity="danger" @click="showToast('error')" />
                    <Button label="Abrir confirmacion" outlined @click="openConfirm" />
                    <Button label="Abrir modal" outlined severity="contrast" @click="state.modalVisible = true" />
                    <Button label="Abrir drawer" text @click="state.drawerVisible = true" />
                    <Button label="Preview loading" text @click="previewLoading" />
                    <Button :label="state.showEmpty ? 'Ocultar empty state' : 'Mostrar empty state'" text @click="state.showEmpty = !state.showEmpty" />
                </div>

                <div class="feedback-note"><strong>Ultima accion:</strong> {{ state.lastAction }}</div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-7">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Banners y mensajes embebidos</h3>
                    <p class="m-0 text-sm text-color-secondary">Ejemplos de mensajes visibles dentro de la pantalla sin depender de toasts.</p>
                </div>

                <div class="feedback-banner feedback-banner-info">
                    <i class="pi pi-info-circle"></i>
                    <span>Banner de informacion general para onboarding, avisos o recordatorios de negocio.</span>
                </div>

                <div class="feedback-banner feedback-banner-success">
                    <i class="pi pi-check-circle"></i>
                    <span>Banner de exito para procesos terminados o activaciones confirmadas.</span>
                </div>

                <div class="feedback-banner feedback-banner-warn">
                    <i class="pi pi-exclamation-triangle"></i>
                    <span>Banner de advertencia para situaciones parciales, datos faltantes o pasos pendientes.</span>
                </div>

                <div class="feedback-banner feedback-banner-danger">
                    <i class="pi pi-times-circle"></i>
                    <span>Banner de error para fallos recuperables o acciones que necesitan soporte.</span>
                </div>

                <StateSkeleton v-if="state.loading" />
                <StateEmpty v-else-if="state.showEmpty" title="Aun no hay elementos para mostrar" description="Usa este patron cuando una lista, tabla o dashboard no tenga informacion disponible todavia." icon="pi pi-inbox" />
            </div>
        </div>

        <div class="col-span-12">
            <DemoPatternGuide
                title="Guia para feedback y overlays"
                :when-to-use="['cuando el usuario necesita confirmacion inmediata de una accion', 'cuando una operacion requiere advertencia previa o contexto adicional', 'cuando la pantalla necesita estados vacios o loaders claros']"
                :avoid-when="[
                    'cuando un toast reemplaza informacion que deberia quedar persistente en pantalla',
                    'cuando un modal contiene demasiadas secciones o flujos largos',
                    'cuando se usan banners por costumbre en lugar de un mensaje realmente util'
                ]"
                :wiring="['usar ToastService para mensajes breves y no bloqueantes', 'usar ConfirmDialog antes de acciones destructivas o irreversibles', 'usar Dialog modal o lateral segun la densidad del contenido']"
                :notes="['cada patron debe decirle al usuario que paso y que puede hacer despues', 'el feedback no debe competir con el contenido principal de la pantalla']"
            />
        </div>

        <Dialog v-model:visible="state.modalVisible" modal header="Modal de ejemplo" :style="{ width: '30rem' }">
            <div class="flex flex-col gap-3">
                <p class="m-0 text-color-secondary">Esta variante de modal es util para formularios cortos, confirmaciones ampliadas o detalles rapidos sin perder contexto.</p>
                <label class="feedback-field">
                    <span>Titulo</span>
                    <InputText placeholder="Nuevo template" fluid />
                </label>
                <label class="feedback-field">
                    <span>Observacion</span>
                    <Textarea rows="3" autoResize placeholder="Comentario corto sobre la accion" fluid />
                </label>
            </div>
            <template #footer>
                <Button label="Cerrar" text @click="state.modalVisible = false" />
                <Button label="Guardar demo" @click="state.modalVisible = false" />
            </template>
        </Dialog>

        <Dialog v-model:visible="state.drawerVisible" modal header="Drawer de ejemplo" position="right" :style="{ width: '28rem', margin: '0' }">
            <div class="flex flex-col gap-3">
                <p class="m-0 text-color-secondary">Este patron puede reutilizarse para paneles de detalle, filtros avanzados o formularios secundarios.</p>
                <div class="surface-ground border-round-xl p-3 text-sm"><strong>Tip:</strong> si el contenido crece, este formato suele sentirse menos invasivo que un modal centrado.</div>
            </div>
            <template #footer>
                <Button label="Cerrar drawer" @click="state.drawerVisible = false" />
            </template>
        </Dialog>

        <ConfirmDialog />
    </div>
</template>

<style scoped>
.feedback-button-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
    gap: 0.75rem;
}

.feedback-note {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-ground);
    padding: 1rem;
    color: var(--text-color-secondary);
}

.feedback-banner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-radius: 1rem;
    padding: 0.95rem 1rem;
    font-weight: 500;
}

.feedback-banner-info {
    background: #e0f2fe;
    color: #0c4a6e;
}

.feedback-banner-success {
    background: #dcfce7;
    color: #166534;
}

.feedback-banner-warn {
    background: #fef3c7;
    color: #92400e;
}

.feedback-banner-danger {
    background: #fee2e2;
    color: #991b1b;
}

.feedback-field {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}
</style>
