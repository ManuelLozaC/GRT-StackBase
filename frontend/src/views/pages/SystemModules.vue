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

async function onToggle(moduleItem) {
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
            <Column header="Tipo" style="min-width: 8rem">
                <template #body="slotProps">
                    <Tag :severity="slotProps.data.is_demo ? 'warning' : 'info'" :value="slotProps.data.is_demo ? 'Demo' : 'Core'" />
                </template>
            </Column>
            <Column header="Estado" style="min-width: 10rem">
                <template #body="slotProps">
                    <div class="flex items-center gap-3">
                        <ToggleSwitch :modelValue="slotProps.data.enabled" @update:modelValue="onToggle(slotProps.data)" />
                        <Tag :severity="slotProps.data.enabled ? 'success' : 'secondary'" :value="slotProps.data.enabled ? 'Activo' : 'Inactivo'" />
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>
</template>
