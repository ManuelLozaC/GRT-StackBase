<script setup>
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';
import { computed, reactive, ref } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const state = reactive({
    form: {
        nombre: 'Sistema de operaciones',
        email: 'owner@grt.com.bo',
        password: '',
        descripcion: 'Formulario base de referencia para modulos de negocio.',
        categoria: 'operations',
        stack: ['ui', 'api'],
        presupuesto: 30000,
        fechaInicio: null,
        prioridad: 'media',
        aprobacion: false,
        activo: true,
        modalidad: 'nuevo',
        adjunto: null
    },
    errors: {
        nombre: '',
        email: '',
        password: '',
        fechaInicio: ''
    }
});

const result = ref(null);

const categories = [
    { label: 'Operaciones', value: 'operations' },
    { label: 'CRM', value: 'crm' },
    { label: 'Portal', value: 'portal' },
    { label: 'ERP', value: 'erp' }
];

const stackOptions = [
    { label: 'UI', value: 'ui' },
    { label: 'API', value: 'api' },
    { label: 'Jobs', value: 'jobs' },
    { label: 'Files', value: 'files' }
];

const priorities = [
    { label: 'Alta', value: 'alta' },
    { label: 'Media', value: 'media' },
    { label: 'Baja', value: 'baja' }
];

const summaryChips = computed(() => [state.form.aprobacion ? 'Requiere aprobacion' : 'Sin aprobacion', state.form.activo ? 'Activo' : 'Inactivo', `Prioridad ${state.form.prioridad}`]);

function clearErrors() {
    Object.keys(state.errors).forEach((key) => {
        state.errors[key] = '';
    });
}

function onSelectFile(event) {
    state.form.adjunto = event.target.files?.[0] ?? null;
}

function submitForm() {
    clearErrors();

    if (!state.form.nombre.trim()) {
        state.errors.nombre = 'El nombre es obligatorio.';
    }

    if (!state.form.email.trim()) {
        state.errors.email = 'El correo es obligatorio.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(state.form.email)) {
        state.errors.email = 'El correo no tiene un formato valido.';
    }

    if (!state.form.password) {
        state.errors.password = 'La contrasena de ejemplo es obligatoria para este formulario.';
    }

    if (!state.form.fechaInicio) {
        state.errors.fechaInicio = 'La fecha de inicio es obligatoria.';
    }

    if (Object.values(state.errors).some(Boolean)) {
        toast.add({
            severity: 'warn',
            summary: 'Formulario incompleto',
            detail: 'Corrige los campos marcados para continuar.',
            life: 2600
        });
        return;
    }

    result.value = {
        ...state.form,
        fechaInicio: state.form.fechaInicio instanceof Date ? state.form.fechaInicio.toISOString().slice(0, 10) : state.form.fechaInicio,
        adjunto: state.form.adjunto?.name ?? null
    };

    toast.add({
        severity: 'success',
        summary: 'Formulario valido',
        detail: 'El payload demo se genero correctamente.',
        life: 2600
    });
}

function resetForm() {
    state.form.nombre = '';
    state.form.email = '';
    state.form.password = '';
    state.form.descripcion = '';
    state.form.categoria = 'operations';
    state.form.stack = [];
    state.form.presupuesto = null;
    state.form.fechaInicio = null;
    state.form.prioridad = 'media';
    state.form.aprobacion = false;
    state.form.activo = true;
    state.form.modalidad = 'nuevo';
    state.form.adjunto = null;
    result.value = null;
    clearErrors();
}
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / UI Forms" class="w-fit" />
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <h2 class="m-0">Forms, inputs y validaciones</h2>
                        <p class="m-0 text-color-secondary">Esta pantalla sirve como referencia operativa para construir formularios reales: inputs, selectores, radios, checkboxes, toggles, file input, validacion y payload final.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Chip v-for="chip in summaryChips" :key="chip" :label="chip" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="card flex flex-col gap-4">
                <div>
                    <h3 class="m-0 mb-2">Formulario de referencia</h3>
                    <p class="m-0 text-sm text-color-secondary">La idea es que un equipo nuevo pueda partir de esta combinacion de campos y feedback visual.</p>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="form-label">Nombre</label>
                        <InputText v-model="state.form.nombre" fluid :invalid="Boolean(state.errors.nombre)" placeholder="Sistema comercial 2026" />
                        <small v-if="state.errors.nombre" class="p-error">{{ state.errors.nombre }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="form-label">Correo responsable</label>
                        <InputText v-model="state.form.email" fluid :invalid="Boolean(state.errors.email)" placeholder="owner@empresa.com" />
                        <small v-if="state.errors.email" class="p-error">{{ state.errors.email }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="form-label">Contrasena de ejemplo</label>
                        <Password v-model="state.form.password" fluid toggleMask :feedback="false" :invalid="Boolean(state.errors.password)" placeholder="********" />
                        <small v-if="state.errors.password" class="p-error">{{ state.errors.password }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="form-label">Categoria</label>
                        <Select v-model="state.form.categoria" :options="categories" optionLabel="label" optionValue="value" placeholder="Seleccionar categoria" fluid />
                    </div>

                    <div class="col-span-12">
                        <label class="form-label">Descripcion</label>
                        <Textarea v-model="state.form.descripcion" rows="4" autoResize fluid placeholder="Describe el objetivo del producto o modulo." />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="form-label">Capacidades incluidas</label>
                        <MultiSelect v-model="state.form.stack" :options="stackOptions" optionLabel="label" optionValue="value" display="chip" placeholder="Seleccionar capacidades" class="w-full" />
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="form-label">Presupuesto</label>
                        <InputNumber v-model="state.form.presupuesto" mode="currency" currency="USD" locale="en-US" fluid />
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="form-label">Fecha de inicio</label>
                        <DatePicker v-model="state.form.fechaInicio" showIcon fluid :invalid="Boolean(state.errors.fechaInicio)" dateFormat="dd/mm/yy" />
                        <small v-if="state.errors.fechaInicio" class="p-error">{{ state.errors.fechaInicio }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="form-label">Prioridad</label>
                        <div class="option-row">
                            <div v-for="priority in priorities" :key="priority.value" class="option-chip">
                                <RadioButton v-model="state.form.prioridad" :inputId="`priority-${priority.value}`" :value="priority.value" />
                                <label :for="`priority-${priority.value}`">{{ priority.label }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="form-label">Modalidad</label>
                        <div class="option-row">
                            <div class="option-chip">
                                <Checkbox v-model="state.form.modalidad" inputId="modalidad-nuevo" binary falseValue="ajuste" trueValue="nuevo" />
                                <label for="modalidad-nuevo">Nuevo</label>
                            </div>
                            <div class="option-chip">
                                <Checkbox v-model="state.form.aprobacion" inputId="requiere-aprobacion" binary />
                                <label for="requiere-aprobacion">Requiere aprobacion</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="form-label">Estado y flujo</label>
                        <div class="toggle-panel">
                            <label class="toggle-row">
                                <span>Activo</span>
                                <ToggleSwitch v-model="state.form.activo" />
                            </label>
                            <label class="toggle-row">
                                <span>Aprobacion</span>
                                <ToggleSwitch v-model="state.form.aprobacion" />
                            </label>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="form-label">Archivo adjunto</label>
                        <input class="w-full" type="file" @change="onSelectFile" />
                        <small class="text-color-secondary">Ejemplo simple para wiring de adjuntos o importaciones.</small>
                    </div>
                </div>

                <div class="form-actions">
                    <Button label="Guardar demo" @click="submitForm" />
                    <Button label="Limpiar" severity="secondary" outlined @click="resetForm" />
                </div>

                <DemoPatternGuide
                    title="Guia para formularios y validacion"
                    :when-to-use="['cuando una entidad necesita varios campos con dependencias claras', 'cuando el usuario debe ver errores por campo y feedback global', 'cuando conviene inspeccionar el payload final durante onboarding']"
                    :avoid-when="['cuando la accion cabe mejor como inline edit o confirmacion corta', 'cuando un formulario largo no esta dividido en bloques comprensibles', 'cuando se mezclan demasiados inputs sin jerarquia']"
                    :wiring="['mantener estado, errores y reset dentro de una estructura estable', 'validar antes de serializar y mostrar el resultado', 'combinar este patron con async patterns si guardar toma tiempo']"
                    :notes="['si el formulario crece mucho, moverlo a una recipe completa', 'si hay archivos o procesos lentos, sumar feedback de progreso']"
                />
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Payload resultante</h3>
                    <p class="m-0 text-sm text-color-secondary">Esta salida ayuda a visualizar el shape esperado para requests reales.</p>
                </div>

                <div v-if="result" class="result-card">
                    <pre>{{ JSON.stringify(result, null, 2) }}</pre>
                </div>

                <StateEmpty v-else title="Aun no hay payload" description="Completa y guarda el formulario para ver el resultado serializado." icon="pi pi-file-edit" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.form-label {
    display: inline-block;
    margin-bottom: 0.45rem;
    font-size: 0.9rem;
    font-weight: 600;
}

.form-actions,
.option-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem;
}

.option-chip {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid var(--surface-border);
    border-radius: 999px;
    background: var(--surface-ground);
    padding: 0.6rem 0.85rem;
}

.toggle-panel {
    display: grid;
    gap: 0.75rem;
}

.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    padding: 0.85rem 1rem;
    background: var(--surface-card);
}

.result-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-ground);
    padding: 1rem;
}

.result-card pre {
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 0.84rem;
}
</style>
