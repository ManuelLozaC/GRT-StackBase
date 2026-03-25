<script setup>
import { settingsStore } from '@/core/settings/settingsStore';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive } from 'vue';

const toast = useToast();

const state = reactive({
    loading: false,
    savingGlobal: false,
    savingOrganization: false,
    globalForm: {},
    organizationForm: {}
});

const globalSettings = computed(() => settingsStore.globalSettings.value);
const organizationSettings = computed(() => settingsStore.organizationSettings.value);

function syncForms() {
    state.globalForm = Object.fromEntries(globalSettings.value.map((setting) => [setting.key, setting.value]));
    state.organizationForm = Object.fromEntries(organizationSettings.value.map((setting) => [setting.key, setting.value]));
}

async function loadSettings() {
    state.loading = true;

    try {
        await settingsStore.initialize(true);
        syncForms();
    } finally {
        state.loading = false;
    }
}

async function saveGlobal() {
    state.savingGlobal = true;

    try {
        await settingsStore.updateGlobal(state.globalForm);
        syncForms();
        toast.add({ severity: 'success', summary: 'Settings globales', detail: 'Se actualizaron correctamente.', life: 3000 });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'No se pudo guardar', detail: error?.response?.data?.mensaje ?? 'Revisa los settings globales.', life: 4000 });
    } finally {
        state.savingGlobal = false;
    }
}

async function saveOrganization() {
    state.savingOrganization = true;

    try {
        await settingsStore.updateOrganization(state.organizationForm);
        syncForms();
        toast.add({ severity: 'success', summary: 'Settings de organizacion', detail: 'Se actualizaron correctamente.', life: 3000 });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'No se pudo guardar', detail: error?.response?.data?.mensaje ?? 'Revisa los settings de organizacion.', life: 4000 });
    } finally {
        state.savingOrganization = false;
    }
}

onMounted(loadSettings);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Administration</div>
            <h1 class="text-3xl font-semibold text-slate-900 mb-3">System Settings</h1>
            <p class="text-slate-600 max-w-3xl">Este panel centraliza configuracion global, feature flags operativas y configuracion por organizacion para que el core no dependa de cambios manuales en codigo.</p>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 xl:col-span-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-slate-900">Global</h2>
                        <Button label="Guardar" icon="pi pi-save" :loading="state.savingGlobal" @click="saveGlobal" />
                    </div>
                    <div class="space-y-4">
                        <div v-for="setting in globalSettings" :key="setting.key">
                            <label class="block text-sm font-semibold text-slate-600 mb-2">{{ setting.label }}</label>
                            <Select v-if="setting.type === 'select'" v-model="state.globalForm[setting.key]" :options="setting.options" optionLabel="label" optionValue="value" class="w-full" />
                            <ToggleSwitch v-else-if="setting.type === 'toggle'" v-model="state.globalForm[setting.key]" />
                            <InputText v-else v-model="state.globalForm[setting.key]" class="w-full" />
                            <div v-if="setting.help" class="text-xs text-slate-500 mt-2">{{ setting.help }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-slate-900">Organizacion activa</h2>
                        <Button label="Guardar" icon="pi pi-save" :loading="state.savingOrganization" @click="saveOrganization" />
                    </div>
                    <div class="space-y-4">
                        <div v-for="setting in organizationSettings" :key="setting.key">
                            <label class="block text-sm font-semibold text-slate-600 mb-2">{{ setting.label }}</label>
                            <Select v-if="setting.type === 'select'" v-model="state.organizationForm[setting.key]" :options="setting.options" optionLabel="label" optionValue="value" class="w-full" />
                            <ToggleSwitch v-else-if="setting.type === 'toggle'" v-model="state.organizationForm[setting.key]" />
                            <InputText v-else v-model="state.organizationForm[setting.key]" class="w-full" />
                            <div v-if="setting.help" class="text-xs text-slate-500 mt-2">{{ setting.help }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
