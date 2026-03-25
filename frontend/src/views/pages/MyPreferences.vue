<script setup>
import { settingsStore } from '@/core/settings/settingsStore';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive } from 'vue';

const toast = useToast();

const state = reactive({
    loading: false,
    saving: false,
    form: {}
});

const userSettings = computed(() => settingsStore.userSettings.value.filter((setting) => !setting.hidden));

function syncForm() {
    state.form = Object.fromEntries(userSettings.value.map((setting) => [setting.key, setting.value]));
}

async function loadPreferences() {
    state.loading = true;

    try {
        await settingsStore.initialize(true);
        syncForm();
    } finally {
        state.loading = false;
    }
}

async function savePreferences() {
    state.saving = true;

    try {
        await settingsStore.updateUser(state.form);
        syncForm();
        toast.add({ severity: 'success', summary: 'Preferencias guardadas', detail: 'Tus preferencias ya se aplicaron.', life: 3000 });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'No se pudo guardar', detail: error?.response?.data?.mensaje ?? 'Revisa tus preferencias.', life: 4000 });
    } finally {
        state.saving = false;
    }
}

onMounted(loadPreferences);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">My Account</div>
            <h1 class="text-3xl font-semibold text-slate-900 mb-3">Preferencias</h1>
            <p class="text-slate-600 max-w-3xl">Aqui puedes ajustar tema, formato de fecha y preferencias de notificacion para que la experiencia del core sea mas personalizable desde el primer modulo.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="space-y-4">
                <div v-for="setting in userSettings" :key="setting.key">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">{{ setting.label }}</label>
                    <Select v-if="setting.type === 'select'" v-model="state.form[setting.key]" :options="setting.options" optionLabel="label" optionValue="value" class="w-full max-w-lg" />
                    <ToggleSwitch v-else-if="setting.type === 'toggle'" v-model="state.form[setting.key]" />
                    <InputText v-else v-model="state.form[setting.key]" class="w-full max-w-lg" />
                    <div v-if="setting.help" class="text-xs text-slate-500 mt-2">{{ setting.help }}</div>
                </div>
            </div>
            <div class="mt-6">
                <Button label="Guardar preferencias" icon="pi pi-save" :loading="state.saving" @click="savePreferences" />
            </div>
        </div>
    </div>
</template>
