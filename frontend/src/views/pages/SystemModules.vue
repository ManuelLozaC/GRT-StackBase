<script setup>
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted } from 'vue';

const toast = useToast();

onMounted(async () => {
    await moduleCatalog.loadModules();
});

const modules = computed(() => moduleCatalog.state.items);
const loading = computed(() => moduleCatalog.state.loading);

function dependencySummary(moduleItem) {
    if (!moduleItem.dependencies?.length) {
        return 'Sin dependencias';
    }

    return moduleItem.dependencies.join(', ');
}

function featureSummary(moduleItem) {
    if (!moduleItem.features?.length) {
        return [];
    }

    return moduleItem.features;
}

function getBlockingMessage(moduleItem) {
    if (moduleItem.dependency_status?.missing?.length) {
        return `Faltan dependencias declaradas: ${moduleItem.dependency_status.missing.join(', ')}.`;
    }

    if (moduleItem.dependency_status?.disabled?.length) {
        return `Debes activar primero: ${moduleItem.dependency_status.disabled.join(', ')}.`;
    }

    if (moduleItem.blocking_dependents?.length) {
        return `No puede deshabilitarse mientras sigan activos: ${moduleItem.blocking_dependents.join(', ')}.`;
    }

    if (moduleItem.is_protected) {
        return 'Modulo protegido del core.';
    }

    return null;
}

function isToggleDisabled(moduleItem) {
    if (moduleItem.enabled) {
        return !moduleItem.can_disable;
    }

    return !moduleItem.can_enable;
}

async function onToggle(moduleItem) {
    if (isToggleDisabled(moduleItem)) {
        const message = getBlockingMessage(moduleItem);

        if (message) {
            toast.add({
                severity: 'warn',
                summary: 'Accion bloqueada',
                detail: message,
                life: 3500
            });
        }

        return;
    }

    const nextValue = !moduleItem.enabled;

    try {
        await moduleCatalog.updateModuleStatus(moduleItem.key, nextValue);
        toast.add({
            severity: 'success',
            summary: 'Modulo actualizado',
            detail: `${moduleItem.name} ahora esta ${nextValue ? 'habilitado' : 'deshabilitado'}.`,
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo actualizar',
            detail: error?.response?.data?.mensaje ?? 'Ocurrio un error al guardar el estado del modulo.',
            life: 4000
        });
    }
}
</script>

<template>
    <div class="card">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h3 class="m-0 text-xl font-semibold">Administracion de modulos</h3>
                <p class="mt-2 mb-0 text-color-secondary">Desde aqui puedes habilitar o deshabilitar modulos plug-in, incluido el modulo de demo para probar funcionalidades genericas.</p>
            </div>
            <Tag severity="contrast" :value="`${modules.length} modulos`" />
        </div>

        <DataTable :value="modules" dataKey="key" :loading="loading">
            <Column field="name" header="Modulo" style="min-width: 16rem">
                <template #body="slotProps">
                    <div class="flex flex-col gap-1">
                        <span class="font-semibold">{{ slotProps.data.name }}</span>
                        <small class="text-color-secondary">{{ slotProps.data.key }}</small>
                    </div>
                </template>
            </Column>
            <Column field="description" header="Descripcion" style="min-width: 24rem" />
            <Column field="version" header="Version" style="min-width: 8rem" />
            <Column header="Contrato" style="min-width: 18rem">
                <template #body="slotProps">
                    <div class="flex flex-col gap-2">
                        <small class="text-color-secondary">
                            <span class="font-semibold text-color">Dependencias:</span>
                            {{ dependencySummary(slotProps.data) }}
                        </small>
                        <div class="flex flex-wrap gap-2" v-if="featureSummary(slotProps.data).length">
                            <Tag v-for="feature in featureSummary(slotProps.data)" :key="feature" severity="secondary" :value="feature" />
                        </div>
                        <small v-if="getBlockingMessage(slotProps.data)" class="text-orange-600">
                            {{ getBlockingMessage(slotProps.data) }}
                        </small>
                    </div>
                </template>
            </Column>
            <Column header="Tipo" style="min-width: 8rem">
                <template #body="slotProps">
                    <Tag :severity="slotProps.data.is_demo ? 'warning' : 'info'" :value="slotProps.data.is_demo ? 'Demo' : 'Core'" />
                </template>
            </Column>
            <Column header="Estado" style="min-width: 10rem">
                <template #body="slotProps">
                    <div class="flex items-center gap-3">
                        <ToggleSwitch :modelValue="slotProps.data.enabled" :disabled="isToggleDisabled(slotProps.data)" @update:modelValue="onToggle(slotProps.data)" />
                        <Tag :severity="slotProps.data.enabled ? 'success' : 'secondary'" :value="slotProps.data.enabled ? 'Activo' : 'Inactivo'" />
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>
</template>
