<script setup>
import { settingsStore } from '@/core/settings/settingsStore';
import { primaryColorOptions, surfacePaletteOptions } from '@/layout/composables/layoutAppearance';
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
const globalAppearanceKeys = ['ui_theme_mode', 'ui_preset', 'ui_primary_color', 'ui_surface_palette', 'ui_menu_mode'];
const globalAppearanceSettings = computed(() => globalSettings.value.filter((setting) => globalAppearanceKeys.includes(setting.key)));
const globalOperationalSettings = computed(() => globalSettings.value.filter((setting) => !globalAppearanceKeys.includes(setting.key)));
const primaryPaletteMap = Object.fromEntries(primaryColorOptions.map((option) => [option.name, option.palette]));
const surfacePaletteMap = Object.fromEntries(surfacePaletteOptions.map((option) => [option.name, option.palette]));

const appearanceOptionHelp = {
    ui_theme_mode: {
        system: 'Respeta el modo claro u oscuro del dispositivo del usuario.',
        light: 'Fuerza una interfaz clara para todos los usuarios.',
        dark: 'Fuerza una interfaz oscura para todos los usuarios.'
    },
    ui_preset: {
        Aura: 'Balanceado y moderno; buen punto de partida para la mayoria de sistemas.',
        Lara: 'Mas sobrio y corporativo, con sensacion clasica.',
        Nora: 'Mas marcado y expresivo, util cuando quieres una interfaz con mas personalidad.'
    },
    ui_menu_mode: {
        static: 'El menu lateral queda visible en desktop y da navegacion mas estable.',
        overlay: 'El menu lateral se superpone al contenido y libera mas espacio horizontal.'
    }
};

const appearanceImpact = {
    ui_theme_mode: 'Impacta el nivel general de luz/contraste del shell.',
    ui_preset: 'Impacta la personalidad base de los componentes, bordes y densidad visual.',
    ui_primary_color: 'Impacta botones, links, badges, focos y acentos principales.',
    ui_surface_palette: 'Impacta fondos, cards, paneles, bordes y contraste general.',
    ui_menu_mode: 'Impacta el comportamiento del menu lateral en todo el shell.'
};

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
        toast.add({ severity: 'success', summary: 'Settings de empresa', detail: 'Se actualizaron correctamente.', life: 3000 });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'No se pudo guardar', detail: error?.response?.data?.mensaje ?? 'Revisa los settings de empresa.', life: 4000 });
    } finally {
        state.savingOrganization = false;
    }
}

onMounted(loadSettings);

function optionDescription(settingKey, optionValue) {
    return appearanceOptionHelp[settingKey]?.[optionValue] ?? '';
}

function currentSettingLabel(setting, value) {
    return setting.options?.find((option) => option.value === value)?.label ?? value;
}

function primarySwatchStyle(colorName) {
    const palette = primaryPaletteMap[colorName] ?? {};

    return {
        background: colorName === 'noir' ? '#111827' : (palette[500] ?? '#10b981')
    };
}

function surfaceSwatchStyle(surfaceName) {
    const palette = surfacePaletteMap[surfaceName] ?? {};

    return {
        background: `linear-gradient(135deg, ${palette[0] ?? '#ffffff'} 0%, ${palette[200] ?? '#e2e8f0'} 55%, ${palette[700] ?? '#334155'} 100%)`
    };
}
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Administration</div>
            <h1 class="text-3xl font-semibold text-slate-900 mb-3">System Settings</h1>
            <p class="text-slate-600 max-w-3xl">Este panel centraliza configuracion global, feature flags operativas y configuracion por empresa para que el core no dependa de cambios manuales en codigo.</p>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 xl:col-span-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Apariencia global</h2>
                            <p class="mt-1 text-sm text-slate-500">Estos valores se aplican a todos los usuarios autenticados y reemplazan el configurador visual del header.</p>
                        </div>
                        <Button label="Guardar" icon="pi pi-save" :loading="state.savingGlobal" @click="saveGlobal" />
                    </div>
                    <div class="appearance-preview-card">
                        <div class="appearance-preview-shell">
                            <div class="appearance-preview-sidebar" :class="state.globalForm.ui_menu_mode === 'overlay' ? 'is-overlay' : 'is-static'"></div>
                            <div class="appearance-preview-main">
                                <div class="appearance-preview-toolbar">
                                    <span class="appearance-preview-primary" :style="primarySwatchStyle(state.globalForm.ui_primary_color || 'emerald')"></span>
                                    <span class="appearance-preview-surface" :style="surfaceSwatchStyle(state.globalForm.ui_surface_palette || 'slate')"></span>
                                </div>
                                <div class="appearance-preview-copy">
                                    <strong>{{ currentSettingLabel(globalAppearanceSettings.find((item) => item.key === 'ui_preset') || { options: [] }, state.globalForm.ui_preset) }}</strong>
                                    <small>{{ appearanceImpact.ui_preset }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="appearance-preview-summary">
                            <span><b>Tema:</b> {{ currentSettingLabel(globalAppearanceSettings.find((item) => item.key === 'ui_theme_mode') || { options: [] }, state.globalForm.ui_theme_mode) }}</span>
                            <span><b>Preset:</b> {{ state.globalForm.ui_preset }}</span>
                            <span><b>Primario:</b> {{ state.globalForm.ui_primary_color }}</span>
                            <span><b>Surface:</b> {{ state.globalForm.ui_surface_palette }}</span>
                            <span><b>Menu:</b> {{ currentSettingLabel(globalAppearanceSettings.find((item) => item.key === 'ui_menu_mode') || { options: [] }, state.globalForm.ui_menu_mode) }}</span>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div v-for="setting in globalAppearanceSettings" :key="setting.key">
                            <label class="block text-sm font-semibold text-slate-600 mb-2">{{ setting.label }}</label>
                            <div v-if="setting.key === 'ui_primary_color'" class="appearance-swatch-grid">
                                <button
                                    v-for="option in setting.options"
                                    :key="option.value"
                                    type="button"
                                    :class="['appearance-swatch-option', { active: state.globalForm[setting.key] === option.value }]"
                                    @click="state.globalForm[setting.key] = option.value"
                                >
                                    <span class="appearance-swatch" :style="primarySwatchStyle(option.value)"></span>
                                    <span>{{ option.label }}</span>
                                </button>
                            </div>
                            <div v-else-if="setting.key === 'ui_surface_palette'" class="appearance-swatch-grid surface">
                                <button
                                    v-for="option in setting.options"
                                    :key="option.value"
                                    type="button"
                                    :class="['appearance-swatch-option', { active: state.globalForm[setting.key] === option.value }]"
                                    @click="state.globalForm[setting.key] = option.value"
                                >
                                    <span class="appearance-swatch surface" :style="surfaceSwatchStyle(option.value)"></span>
                                    <span>{{ option.label }}</span>
                                </button>
                            </div>
                            <div v-else-if="setting.key === 'ui_preset' || setting.key === 'ui_theme_mode' || setting.key === 'ui_menu_mode'" class="appearance-choice-grid">
                                <button
                                    v-for="option in setting.options"
                                    :key="option.value"
                                    type="button"
                                    :class="['appearance-choice-card', { active: state.globalForm[setting.key] === option.value }]"
                                    @click="state.globalForm[setting.key] = option.value"
                                >
                                    <strong>{{ option.label }}</strong>
                                    <small>{{ optionDescription(setting.key, option.value) }}</small>
                                </button>
                            </div>
                            <Select v-else-if="setting.type === 'select'" v-model="state.globalForm[setting.key]" :options="setting.options" optionLabel="label" optionValue="value" class="w-full" />
                            <ToggleSwitch v-else-if="setting.type === 'toggle'" v-model="state.globalForm[setting.key]" />
                            <InputText v-else v-model="state.globalForm[setting.key]" class="w-full" />
                            <div v-if="appearanceImpact[setting.key]" class="text-xs text-slate-600 mt-2">{{ appearanceImpact[setting.key] }}</div>
                            <div v-if="setting.help" class="text-xs text-slate-500 mt-2">{{ setting.help }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Empresa activa</h2>
                            <p class="mt-1 text-sm text-slate-500">Locale, moneda y zona horaria base de la empresa seleccionada.</p>
                        </div>
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

            <div class="col-span-12">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Operacion global</h2>
                            <p class="mt-1 text-sm text-slate-500">Banners, feature flags y ajustes operativos del shell.</p>
                        </div>
                        <Button label="Guardar" icon="pi pi-save" :loading="state.savingGlobal" @click="saveGlobal" />
                    </div>
                    <div class="grid grid-cols-12 gap-4">
                        <div v-for="setting in globalOperationalSettings" :key="setting.key" class="col-span-12 md:col-span-6 xl:col-span-4">
                            <label class="block text-sm font-semibold text-slate-600 mb-2">{{ setting.label }}</label>
                            <Select v-if="setting.type === 'select'" v-model="state.globalForm[setting.key]" :options="setting.options" optionLabel="label" optionValue="value" class="w-full" />
                            <ToggleSwitch v-else-if="setting.type === 'toggle'" v-model="state.globalForm[setting.key]" />
                            <InputText v-else v-model="state.globalForm[setting.key]" class="w-full" />
                            <div v-if="setting.help" class="text-xs text-slate-500 mt-2">{{ setting.help }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.appearance-preview-card {
    display: grid;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 1.25rem;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}

.appearance-preview-shell {
    display: grid;
    grid-template-columns: 5rem 1fr;
    gap: 0.85rem;
}

.appearance-preview-sidebar {
    min-height: 6rem;
    border-radius: 1rem;
    background: #cbd5e1;
}

.appearance-preview-sidebar.is-overlay {
    background: repeating-linear-gradient(135deg, #cbd5e1 0, #cbd5e1 10px, #e2e8f0 10px, #e2e8f0 20px);
}

.appearance-preview-main {
    display: grid;
    gap: 0.75rem;
    padding: 0.9rem;
    border-radius: 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.appearance-preview-toolbar {
    display: flex;
    gap: 0.75rem;
}

.appearance-preview-primary,
.appearance-preview-surface {
    display: inline-flex;
    width: 3rem;
    height: 1.25rem;
    border-radius: 999px;
}

.appearance-preview-copy {
    display: grid;
    gap: 0.2rem;
}

.appearance-preview-copy small,
.appearance-preview-summary {
    color: #64748b;
}

.appearance-preview-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem;
    font-size: 0.85rem;
}

.appearance-choice-grid,
.appearance-swatch-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
}

.appearance-choice-card,
.appearance-swatch-option {
    display: grid;
    gap: 0.5rem;
    justify-items: start;
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 1rem;
    background: #f8fafc;
    padding: 0.85rem;
    text-align: left;
    cursor: pointer;
    transition: 0.2s ease;
}

.appearance-choice-card small,
.appearance-swatch-option {
    color: #64748b;
}

.appearance-choice-card.active,
.appearance-swatch-option.active {
    border-color: #0ea5e9;
    background: #eff6ff;
    box-shadow: inset 0 0 0 1px #0ea5e9;
}

.appearance-swatch {
    width: 100%;
    height: 2rem;
    border-radius: 0.85rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
}

.appearance-swatch.surface {
    height: 2.5rem;
}
</style>
