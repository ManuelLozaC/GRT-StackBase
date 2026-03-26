<script setup>
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import { computed, reactive, ref } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const confirm = useConfirm();

const feedbackState = reactive({
    dialogVisible: false,
    loadingPreview: false,
    showEmptyState: false,
    confirmedAction: 'Todavia no ejecutaste ninguna confirmacion.'
});

const formState = reactive({
    nombre: 'TalentHub CRM',
    email: 'producto@grt.com.bo',
    descripcion: 'Base inicial para un sistema multi-sucursal con aprobaciones.',
    categoria: 'erp',
    stacks: ['api', 'ui'],
    presupuesto: 12500,
    fechaInicio: null,
    requiereAprobacion: true,
    estadoActivo: true
});

const formErrors = reactive({
    nombre: '',
    email: '',
    fechaInicio: ''
});

const submittedPayload = ref(null);
const tableFilter = ref('');

const categoryOptions = [
    { label: 'ERP', value: 'erp' },
    { label: 'CRM', value: 'crm' },
    { label: 'Portal', value: 'portal' },
    { label: 'Operaciones', value: 'operations' }
];

const stackOptions = [
    { label: 'API', value: 'api' },
    { label: 'UI', value: 'ui' },
    { label: 'Jobs', value: 'jobs' },
    { label: 'Integraciones', value: 'integrations' }
];

const exampleRows = [
    { id: 1, modulo: 'Auth', patron: 'Login con alias', estado: 'Listo' },
    { id: 2, modulo: 'Files', patron: 'Subida a Spaces', estado: 'Listo' },
    { id: 3, modulo: 'Jobs', patron: 'Queue + scheduler', estado: 'Listo' },
    { id: 4, modulo: 'UI', patron: 'Form + validacion', estado: 'En diseno' }
];

const filteredRows = computed(() => {
    const term = tableFilter.value.trim().toLowerCase();

    if (!term) {
        return exampleRows;
    }

    return exampleRows.filter((row) => Object.values(row).some((value) => String(value).toLowerCase().includes(term)));
});

function triggerToast(severity) {
    const catalog = {
        success: {
            summary: 'Guardado correcto',
            detail: 'La accion se completo como ejemplo del patron de exito.'
        },
        info: {
            summary: 'Informacion operativa',
            detail: 'Puedes usar este patron para mensajes neutrales o de estado.'
        },
        warn: {
            summary: 'Atencion requerida',
            detail: 'Este ejemplo muestra un warning no bloqueante.'
        },
        error: {
            summary: 'Error controlado',
            detail: 'Este patron sirve para fallos recuperables visibles al usuario.'
        }
    };

    toast.add({
        severity,
        life: 2500,
        ...catalog[severity]
    });
}

function openConfirm() {
    confirm.require({
        header: 'Confirmar ejemplo',
        message: 'Esta accion no cambia datos reales. Solo demuestra el patron de confirmacion.',
        icon: 'pi pi-question-circle',
        acceptLabel: 'Aceptar',
        rejectLabel: 'Cancelar',
        accept: () => {
            feedbackState.confirmedAction = 'Aceptaste la confirmacion demo.';
            triggerToast('success');
        },
        reject: () => {
            feedbackState.confirmedAction = 'Cancelaste la confirmacion demo.';
            triggerToast('info');
        }
    });
}

function previewLoadingState() {
    feedbackState.loadingPreview = true;

    window.setTimeout(() => {
        feedbackState.loadingPreview = false;
    }, 1300);
}

function resetErrors() {
    formErrors.nombre = '';
    formErrors.email = '';
    formErrors.fechaInicio = '';
}

function submitDemoForm() {
    resetErrors();

    if (!formState.nombre.trim()) {
        formErrors.nombre = 'El nombre del proyecto es obligatorio.';
    }

    if (!formState.email.trim()) {
        formErrors.email = 'El correo responsable es obligatorio.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formState.email)) {
        formErrors.email = 'El correo no tiene un formato valido.';
    }

    if (!formState.fechaInicio) {
        formErrors.fechaInicio = 'La fecha de inicio es obligatoria.';
    }

    if (formErrors.nombre || formErrors.email || formErrors.fechaInicio) {
        triggerToast('warn');
        return;
    }

    submittedPayload.value = {
        ...formState,
        fechaInicio: formState.fechaInicio instanceof Date ? formState.fechaInicio.toISOString().slice(0, 10) : formState.fechaInicio
    };

    triggerToast('success');
}

function resetDemoForm() {
    formState.nombre = '';
    formState.email = '';
    formState.descripcion = '';
    formState.categoria = 'erp';
    formState.stacks = [];
    formState.presupuesto = null;
    formState.fechaInicio = null;
    formState.requiereAprobacion = false;
    formState.estadoActivo = true;
    submittedPayload.value = null;
    resetErrors();
}
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / UI Showcase" class="w-fit" />
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <h2 class="m-0">UI Showcase del StackBase</h2>
                        <p class="m-0 text-color-secondary">
                            Esta pantalla funciona como biblioteca viva de patrones de interfaz. La idea es que cualquier nuevo proyecto pueda copiar de aqui el wiring base de feedback, modals, forms, inputs, datepickers, tablas y estados visuales.
                        </p>
                    </div>
                    <div class="showcase-pill-row">
                        <Tag value="Toasts" severity="success" />
                        <Tag value="Modals" severity="info" />
                        <Tag value="Forms" severity="warning" />
                        <Tag value="Tables" severity="contrast" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-5">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Feedback y estado</h3>
                    <p class="m-0 text-sm text-color-secondary">Patrones base para toasts, confirmaciones, modal, loader y empty state.</p>
                </div>

                <div class="showcase-button-grid">
                    <Button label="Toast success" severity="success" @click="triggerToast('success')" />
                    <Button label="Toast info" severity="info" @click="triggerToast('info')" />
                    <Button label="Toast warn" severity="warn" @click="triggerToast('warn')" />
                    <Button label="Toast error" severity="danger" @click="triggerToast('error')" />
                    <Button label="Confirmacion" outlined @click="openConfirm" />
                    <Button label="Abrir modal" outlined severity="contrast" @click="feedbackState.dialogVisible = true" />
                    <Button label="Preview loader" text @click="previewLoadingState" />
                    <Button :label="feedbackState.showEmptyState ? 'Ocultar empty state' : 'Mostrar empty state'" text @click="feedbackState.showEmptyState = !feedbackState.showEmptyState" />
                </div>

                <div class="showcase-banner showcase-banner-info">
                    <i class="pi pi-info-circle"></i>
                    <span>Banner informativo reutilizable para mensajes globales del producto.</span>
                </div>

                <div class="showcase-banner showcase-banner-success">
                    <i class="pi pi-check-circle"></i>
                    <span>Banner de exito para procesos terminados o habilitaciones completadas.</span>
                </div>

                <div class="showcase-banner showcase-banner-warn">
                    <i class="pi pi-exclamation-triangle"></i>
                    <span>Banner de advertencia para procesos parciales o acciones pendientes.</span>
                </div>

                <div class="surface-ground border-round-xl p-3 text-sm text-color-secondary"><strong class="text-color">Ultima confirmacion:</strong> {{ feedbackState.confirmedAction }}</div>

                <StateSkeleton v-if="feedbackState.loadingPreview" />

                <StateEmpty v-else-if="feedbackState.showEmptyState" title="Sin resultados para este filtro" description="Este ejemplo representa la vista vacia recomendada cuando una tabla o listado no devuelve datos." icon="pi pi-inbox" />

                <Dialog v-model:visible="feedbackState.dialogVisible" modal header="Modal demo" :style="{ width: '32rem' }">
                    <div class="flex flex-col gap-3">
                        <p class="m-0 text-color-secondary">Este modal sirve como referencia para formularios cortos, confirmaciones ampliadas o detalles rapidos sin salir del contexto actual.</p>
                        <div class="grid grid-cols-12 gap-3">
                            <div class="col-span-12 md:col-span-6">
                                <label class="showcase-field-label">Titulo</label>
                                <InputText placeholder="Nuevo componente" fluid />
                            </div>
                            <div class="col-span-12 md:col-span-6">
                                <label class="showcase-field-label">Estado</label>
                                <Select
                                    :options="[
                                        { label: 'Borrador', value: 'draft' },
                                        { label: 'Publicado', value: 'published' }
                                    ]"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Seleccionar"
                                    fluid
                                />
                            </div>
                        </div>
                    </div>
                    <template #footer>
                        <Button label="Cerrar" text @click="feedbackState.dialogVisible = false" />
                        <Button label="Guardar demo" @click="feedbackState.dialogVisible = false" />
                    </template>
                </Dialog>
                <ConfirmDialog />
            </div>
        </div>

        <div class="col-span-12 xl:col-span-7">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Tipografia y contenido</h3>
                    <p class="m-0 text-sm text-color-secondary">Jerarquia basica para layouts administrativos y pantallas de producto.</p>
                </div>

                <div class="flex flex-col gap-2">
                    <h1 class="m-0">Titulo principal de pagina</h1>
                    <h3 class="m-0">Subtitulo o bloque de seccion</h3>
                    <p class="m-0 text-color-secondary">Este parrafo sirve como ejemplo del tono y densidad recomendados para explicar una pantalla sin sobrecargar la interfaz.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Tag severity="success" value="Activo" />
                    <Tag severity="warn" value="Pendiente" />
                    <Tag severity="danger" value="Bloqueado" />
                    <Chip label="TalentHub" icon="pi pi-building" />
                    <Chip label="Aprobacion requerida" icon="pi pi-check-square" />
                </div>

                <div class="showcase-copy-block">
                    <strong>Texto auxiliar</strong>
                    <p class="m-0">Usa bloques como este para observaciones, instrucciones operativas o contexto de onboarding dentro del shell.</p>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-7">
            <div class="card flex flex-col gap-4">
                <div>
                    <h3 class="m-0 mb-2">Formularios, inputs y datepicker</h3>
                    <p class="m-0 text-sm text-color-secondary">Ejemplo base de formulario administrativo con validacion visible, estados y payload resultante.</p>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="showcase-field-label">Nombre del proyecto</label>
                        <InputText v-model="formState.nombre" fluid :invalid="Boolean(formErrors.nombre)" placeholder="Sistema comercial 2026" />
                        <small v-if="formErrors.nombre" class="p-error">{{ formErrors.nombre }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="showcase-field-label">Correo responsable</label>
                        <InputText v-model="formState.email" fluid :invalid="Boolean(formErrors.email)" placeholder="responsable@empresa.com" />
                        <small v-if="formErrors.email" class="p-error">{{ formErrors.email }}</small>
                    </div>

                    <div class="col-span-12">
                        <label class="showcase-field-label">Descripcion</label>
                        <Textarea v-model="formState.descripcion" rows="4" fluid autoResize placeholder="Describe el objetivo del modulo o sistema." />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="showcase-field-label">Categoria</label>
                        <Select v-model="formState.categoria" :options="categoryOptions" optionLabel="label" optionValue="value" placeholder="Seleccionar categoria" fluid />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="showcase-field-label">Capacidades incluidas</label>
                        <MultiSelect v-model="formState.stacks" :options="stackOptions" optionLabel="label" optionValue="value" display="chip" placeholder="Seleccionar capacidades" class="w-full" />
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="showcase-field-label">Presupuesto estimado</label>
                        <InputNumber v-model="formState.presupuesto" mode="currency" currency="USD" locale="en-US" fluid />
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="showcase-field-label">Fecha de inicio</label>
                        <DatePicker v-model="formState.fechaInicio" showIcon fluid :invalid="Boolean(formErrors.fechaInicio)" dateFormat="dd/mm/yy" />
                        <small v-if="formErrors.fechaInicio" class="p-error">{{ formErrors.fechaInicio }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-4 flex flex-col justify-end">
                        <div class="showcase-switch-row">
                            <label class="showcase-switch-label">
                                <span>Requiere aprobacion</span>
                                <ToggleSwitch v-model="formState.requiereAprobacion" />
                            </label>
                            <label class="showcase-switch-label">
                                <span>Activo</span>
                                <ToggleSwitch v-model="formState.estadoActivo" />
                            </label>
                        </div>
                    </div>
                </div>

                <div class="showcase-button-row">
                    <Button label="Guardar demo" @click="submitDemoForm" />
                    <Button label="Limpiar formulario" severity="secondary" outlined @click="resetDemoForm" />
                </div>

                <div v-if="submittedPayload" class="showcase-result-card">
                    <div class="font-semibold mb-2">Payload resultante</div>
                    <pre>{{ JSON.stringify(submittedPayload, null, 2) }}</pre>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-5">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Tabla y filtro rapido</h3>
                    <p class="m-0 text-sm text-color-secondary">Referencia minima para listados con filtro local, tags de estado y acciones visibles.</p>
                </div>

                <InputText v-model="tableFilter" fluid placeholder="Filtrar por modulo, patron o estado" />

                <DataTable :value="filteredRows" size="small" paginator :rows="4" responsiveLayout="scroll">
                    <Column field="modulo" header="Modulo" />
                    <Column field="patron" header="Patron" />
                    <Column field="estado" header="Estado">
                        <template #body="{ data }">
                            <Tag :severity="data.estado === 'Listo' ? 'success' : 'warning'" :value="data.estado" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <div class="col-span-12">
            <DemoPatternGuide
                title="Guia para usar el UI Showcase"
                :when-to-use="[
                    'cuando necesitas una vista rapida de varios patrones del stack en una sola pantalla',
                    'cuando estas arrancando un modulo y aun no sabes que demo especifica copiar',
                    'cuando quieres presentar la biblioteca visual completa al equipo'
                ]"
                :avoid-when="[
                    'cuando ya sabes que necesitas una demo mas especializada como forms o async patterns',
                    'cuando buscas una recipe completa de pantalla y no un muestrario transversal',
                    'cuando una decision necesita reglas mas profundas que un showcase general'
                ]"
                :wiring="['usar esta pantalla como indice vivo del Demo Module', 'luego saltar a las demos especializadas para copiar wiring mas preciso', 'mantener aqui ejemplos amplios y dejar el detalle fino en las sub-secciones']"
                :notes="['esta vista funciona como mapa rapido del stack visual', 'si una seccion crece mucho, debe migrar a una demo dedicada']"
            />
        </div>
    </div>
</template>

<style scoped>
.showcase-pill-row,
.showcase-button-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.showcase-button-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
    gap: 0.75rem;
}

.showcase-banner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-radius: 1rem;
    padding: 0.9rem 1rem;
    font-weight: 500;
}

.showcase-banner-info {
    background: #e0f2fe;
    color: #0c4a6e;
}

.showcase-banner-success {
    background: #dcfce7;
    color: #166534;
}

.showcase-banner-warn {
    background: #fef3c7;
    color: #92400e;
}

.showcase-field-label {
    display: inline-block;
    margin-bottom: 0.45rem;
    font-size: 0.9rem;
    font-weight: 600;
}

.showcase-copy-block,
.showcase-result-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-ground);
    padding: 1rem;
}

.showcase-result-card pre {
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 0.85rem;
}

.showcase-switch-row {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.showcase-switch-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    padding: 0.9rem 1rem;
    background: var(--surface-card);
}

@media (max-width: 768px) {
    .showcase-button-grid {
        grid-template-columns: 1fr;
    }
}
</style>
