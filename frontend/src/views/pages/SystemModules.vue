<script setup>
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import api from '@/service/api';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive } from 'vue';

const toast = useToast();
const state = reactive({
    settingsDialogVisible: false,
    settingsLoading: false,
    settingsSaving: false,
    selectedModule: null,
    settings: [],
    form: {}
});

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

function operationalSummary(moduleItem) {
    return [
        `${moduleItem.permissions?.length ?? 0} permisos`,
        `${moduleItem.settings?.length ?? 0} settings`,
        `${moduleItem.jobs?.length ?? 0} jobs`,
        `${moduleItem.webhooks?.length ?? 0} webhooks`,
        `${moduleItem.dashboards?.length ?? 0} dashboards`
    ];
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

function hasSettings(moduleItem) {
    return (moduleItem.settings ?? []).length > 0;
}

function resetSettingsState() {
    state.settingsDialogVisible = false;
    state.settingsLoading = false;
    state.settingsSaving = false;
    state.selectedModule = null;
    state.settings = [];
    state.form = {};
}

async function openSettings(moduleItem) {
    state.settingsDialogVisible = true;
    state.settingsLoading = true;
    state.selectedModule = moduleItem;

    try {
        const response = await api.get(`/v1/modules/${moduleItem.key}/settings`);
        state.settings = response.data.datos ?? [];
        state.form = Object.fromEntries(state.settings.map((setting) => [setting.key, setting.value]));
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudieron cargar los settings',
            detail: error?.response?.data?.mensaje ?? 'Ocurrio un error al cargar la configuracion del modulo.',
            life: 4000
        });
        resetSettingsState();
    } finally {
        state.settingsLoading = false;
    }
}

async function saveSettings() {
    if (!state.selectedModule) {
        return;
    }

    state.settingsSaving = true;

    try {
        const response = await api.patch(`/v1/modules/${state.selectedModule.key}/settings`, {
            settings: state.form
        });

        state.settings = response.data.datos ?? [];
        state.form = Object.fromEntries(state.settings.map((setting) => [setting.key, setting.value]));

        toast.add({
            severity: 'success',
            summary: 'Settings actualizados',
            detail: `La configuracion de ${state.selectedModule.name} se guardo correctamente.`,
            life: 3000
        });

        state.settingsDialogVisible = false;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudieron guardar los settings',
            detail: error?.response?.data?.mensaje ?? 'Revisa los valores enviados al modulo.',
            life: 4000
        });
    } finally {
        state.settingsSaving = false;
    }
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

        <div class="overflow-x-auto">
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
                            <div v-if="featureSummary(slotProps.data).length" class="flex flex-wrap gap-2">
                                <Tag v-for="feature in featureSummary(slotProps.data)" :key="feature" severity="secondary" :value="feature" />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Tag v-for="item in operationalSummary(slotProps.data)" :key="item" severity="contrast" :value="item" />
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
                <Column header="Configuracion" style="min-width: 11rem">
                    <template #body="slotProps">
                        <Button v-if="hasSettings(slotProps.data)" label="Settings" icon="pi pi-sliders-h" text @click="openSettings(slotProps.data)" />
                        <span v-else class="text-sm text-color-secondary">Sin settings</span>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="state.settingsDialogVisible" modal :header="state.selectedModule ? `Settings de ${state.selectedModule.name}` : 'Settings'" :style="{ width: '42rem' }" @hide="resetSettingsState">
            <div v-if="state.settingsLoading" class="py-8 text-center text-color-secondary">Cargando settings del modulo...</div>
            <div v-else-if="!state.settings.length" class="py-8 text-center text-color-secondary">Este modulo no expone settings configurables.</div>
            <div v-else class="app-form-section">
                <div class="app-form-section-header">
                    <div class="app-form-section-title">Settings del modulo</div>
                    <p class="app-form-section-description">Cada cambio actualiza la configuracion declarada del modulo seleccionado dentro de la empresa activa.</p>
                </div>
                <div class="grid grid-cols-12 gap-4">
                    <div v-for="setting in state.settings" :key="setting.key" class="col-span-12">
                        <label class="block text-sm font-semibold mb-2">{{ setting.label }}</label>
                        <small v-if="setting.help" class="block mb-2 text-color-secondary">{{ setting.help }}</small>
                        <ToggleSwitch v-if="setting.type === 'toggle'" v-model="state.form[setting.key]" />
                        <InputNumber v-else-if="setting.type === 'number'" v-model="state.form[setting.key]" class="w-full" :useGrouping="false" fluid />
                        <Select v-else-if="setting.type === 'select'" v-model="state.form[setting.key]" :options="setting.options" optionLabel="label" optionValue="value" class="w-full" />
                        <InputText v-else v-model="state.form[setting.key]" class="w-full" />
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="app-dialog-footer">
                    <Button class="app-button-standard" label="Cerrar" severity="secondary" outlined @click="state.settingsDialogVisible = false" />
                    <Button class="app-button-standard" label="Guardar settings" :loading="state.settingsSaving" @click="saveSettings" />
                </div>
            </template>
        </Dialog>
    </div>
</template>
