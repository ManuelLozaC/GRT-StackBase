<script setup>
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';
import { computed, reactive } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const state = reactive({
    canalPreferido: 'email',
    visibilidad: 'team',
    prioridad: 7,
    presupuestoMinimo: 5000,
    rangoFechas: null,
    password: '',
    observaciones: 'Este ejemplo reune entradas mas avanzadas o combinadas.',
    etiquetas: ['core', 'demo'],
    areas: ['ventas', 'operaciones'],
    intensidad: 'balanced'
});

const channelOptions = [
    { label: 'Email', value: 'email' },
    { label: 'WhatsApp', value: 'whatsapp' },
    { label: 'Push', value: 'push' }
];

const visibilityOptions = [
    { label: 'Equipo', value: 'team' },
    { label: 'Sucursal', value: 'branch' },
    { label: 'Empresa', value: 'company' }
];

const tagOptions = [
    { label: 'Core', value: 'core' },
    { label: 'Demo', value: 'demo' },
    { label: 'UI', value: 'ui' },
    { label: 'Ops', value: 'ops' }
];

const areaOptions = [
    { label: 'Ventas', value: 'ventas' },
    { label: 'Operaciones', value: 'operaciones' },
    { label: 'Administracion', value: 'administracion' },
    { label: 'Soporte', value: 'soporte' }
];

const intensityOptions = [
    { label: 'Ligero', value: 'light' },
    { label: 'Balanceado', value: 'balanced' },
    { label: 'Estricto', value: 'strict' }
];

const characterCount = computed(() => state.observaciones.length);

function saveAdvancedInputs() {
    toast.add({
        severity: 'success',
        summary: 'Inputs capturados',
        detail: 'El ejemplo demuestra combinaciones mas avanzadas de inputs reutilizables.',
        life: 2600
    });
}
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / UI Advanced Inputs" class="w-fit" />
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <h2 class="m-0">Inputs avanzados y combinaciones utiles</h2>
                        <p class="m-0 text-color-secondary">Esta demo se enfoca en entradas menos triviales: `SelectButton`, rangos de fecha, password con feedback, contadores de texto y combinaciones mas ricas para configuraciones.</p>
                    </div>
                    <Button label="Guardar demo" @click="saveAdvancedInputs" />
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="card flex flex-col gap-4">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="advanced-label">Canal preferido</label>
                        <SelectButton v-model="state.canalPreferido" :options="channelOptions" optionLabel="label" optionValue="value" :allowEmpty="false" />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="advanced-label">Visibilidad</label>
                        <SelectButton v-model="state.visibilidad" :options="visibilityOptions" optionLabel="label" optionValue="value" :allowEmpty="false" />
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="advanced-label">Prioridad numerica</label>
                        <InputNumber v-model="state.prioridad" :min="1" :max="10" showButtons fluid />
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="advanced-label">Presupuesto minimo</label>
                        <InputNumber v-model="state.presupuestoMinimo" mode="currency" currency="USD" locale="en-US" fluid />
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="advanced-label">Intensidad</label>
                        <Select v-model="state.intensidad" :options="intensityOptions" optionLabel="label" optionValue="value" fluid />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="advanced-label">Rango de fechas</label>
                        <DatePicker v-model="state.rangoFechas" selectionMode="range" showIcon fluid dateFormat="dd/mm/yy" />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="advanced-label">Password con feedback</label>
                        <Password v-model="state.password" fluid toggleMask placeholder="Define una clave demo" />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="advanced-label">Etiquetas</label>
                        <MultiSelect v-model="state.etiquetas" :options="tagOptions" optionLabel="label" optionValue="value" display="chip" class="w-full" placeholder="Seleccionar etiquetas" />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="advanced-label">Areas impactadas</label>
                        <MultiSelect v-model="state.areas" :options="areaOptions" optionLabel="label" optionValue="value" display="chip" class="w-full" placeholder="Seleccionar areas" />
                    </div>

                    <div class="col-span-12">
                        <label class="advanced-label">Observaciones</label>
                        <Textarea v-model="state.observaciones" rows="4" autoResize fluid />
                        <small class="text-color-secondary">Caracteres: {{ characterCount }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Cuándo usar estos inputs</h3>
                    <p class="m-0 text-sm text-color-secondary">Guia rapida para elegir patrones mas ricos sin sobrecargar formularios simples.</p>
                </div>

                <div class="advanced-tip-card">
                    <div class="font-semibold mb-2">SelectButton</div>
                    <p class="m-0 text-sm text-color-secondary">Mejor cuando hay pocas opciones mutuamente excluyentes y el usuario se beneficia de verlas todas a la vez.</p>
                </div>

                <div class="advanced-tip-card">
                    <div class="font-semibold mb-2">Date range</div>
                    <p class="m-0 text-sm text-color-secondary">Ideal para filtros, reportes o ventanas operativas donde el rango importa mas que una fecha puntual.</p>
                </div>

                <div class="advanced-tip-card">
                    <div class="font-semibold mb-2">Password con feedback</div>
                    <p class="m-0 text-sm text-color-secondary">Util para onboarding, alta de usuarios y configuraciones sensibles.</p>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <DemoPatternGuide
                title="Guia para inputs avanzados"
                :when-to-use="[
                    'cuando el usuario se beneficia de controles mas expresivos que un input basico',
                    'cuando hay pocas opciones visibles y conviene compararlas en la misma linea',
                    'cuando filtros y configuraciones necesitan mayor precision'
                ]"
                :avoid-when="['cuando un select simple resuelve lo mismo con menos friccion', 'cuando demasiados controles especiales complican un formulario comun', 'cuando el valor seleccionado no necesita tanta riqueza visual']"
                :wiring="['combinar inputs avanzados con labels claros y ayuda contextual', 'usar SelectButton solo con pocas opciones y nombres cortos', 'mostrar feedback resumido para que el usuario entienda el impacto de su eleccion']"
                :notes="['este tipo de input gana valor cuando reduce clicks o ambiguedad', 'si un control especial no aporta claridad, volver a un patron mas simple']"
            />
        </div>
    </div>
</template>

<style scoped>
.advanced-label {
    display: inline-block;
    margin-bottom: 0.45rem;
    font-size: 0.9rem;
    font-weight: 600;
}

.advanced-tip-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-ground);
    padding: 1rem;
}
</style>
