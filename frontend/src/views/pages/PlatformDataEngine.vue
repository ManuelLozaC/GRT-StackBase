<script setup>
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import { settingsStore } from '@/core/settings/settingsStore';
import { formatDateTime } from '@/core/settings/formatters';
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import api from '@/service/api';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

const toast = useToast();
const confirm = useConfirm();
const router = useRouter();

const state = reactive({
    resources: [],
    selectedResourceKey: null,
    loadingResources: false,
    loadingRecords: false,
    saving: false,
    records: [],
    total: 0,
    page: 1,
    perPage: 10,
    search: '',
    sortBy: null,
    sortDirection: null,
    filters: {},
    dialogVisible: false,
    importDialogVisible: false,
    editingRecord: null,
    form: {},
    importing: false,
    exporting: false,
    loadingTransfers: false,
    transferRuns: [],
    importFile: null,
    relationOptions: {},
    visibleColumnKeys: []
});
const importInput = ref(null);

const currentResource = computed(() => state.resources.find((resource) => resource.key === state.selectedResourceKey) ?? null);
const resources = computed(() => state.resources);
const tableFields = computed(() => currentResource.value?.table_fields ?? []);
const visibleTableFields = computed(() => tableFields.value.filter((field) => state.visibleColumnKeys.includes(field.key)));
const formFields = computed(() => currentResource.value?.form_fields ?? []);
const filterFields = computed(() => currentResource.value?.filter_fields ?? []);
const customFields = computed(() => currentResource.value?.custom_fields ?? []);
const perPageOptions = computed(() => currentResource.value?.per_page_options ?? [10, 25, 50]);
const hasResources = computed(() => state.resources.length > 0);
const pageFirst = computed(() => (state.page - 1) * state.perPage);
const resourceCapabilities = computed(
    () =>
        currentResource.value?.capabilities ?? {
            create: true,
            update: true,
            delete: true,
            export: true,
            import: true
        }
);
const tableSize = computed(() => (settingsStore.getSettingValue('user', 'dense_tables', false) ? 'small' : null));

function currentDataEnginePreferences() {
    return settingsStore.getSettingValue('user', 'data_engine_preferences', {}) ?? {};
}

function defaultVisibleColumnsForCurrentResource() {
    return tableFields.value.map((field) => field.key);
}

function syncVisibleColumnsFromPreferences() {
    const resourceKey = state.selectedResourceKey;
    const preferences = currentDataEnginePreferences();
    const preferred = Array.isArray(preferences?.[resourceKey]?.visible_columns) ? preferences[resourceKey].visible_columns : [];
    const validKeys = tableFields.value.map((field) => field.key);
    const selected = preferred.filter((key) => validKeys.includes(key));

    state.visibleColumnKeys = selected.length ? selected : validKeys;
}

async function saveViewPreferences() {
    const resourceKey = state.selectedResourceKey;

    if (!resourceKey) {
        return;
    }

    const preferences = {
        ...currentDataEnginePreferences(),
        [resourceKey]: {
            ...(currentDataEnginePreferences()?.[resourceKey] ?? {}),
            visible_columns: state.visibleColumnKeys
        }
    };

    try {
        await settingsStore.updateUser({
            data_engine_preferences: preferences
        });
    } catch {
        // Si la persistencia falla, mantenemos la preferencia local actual en memoria.
    }
}

function humanizeValue(field, value, displayValue = null) {
    if (field.relation?.display_key && displayValue) {
        return displayValue;
    }

    if (value === null || value === '') {
        return 'Sin dato';
    }

    if (field.options?.length) {
        return field.options.find((option) => option.value === value)?.label ?? value;
    }

    return value;
}

function tagSeverity(fieldKey, value) {
    if (fieldKey === 'estado') {
        return (
            {
                lead: 'warning',
                active: 'success',
                inactive: 'secondary'
            }[value] ?? 'contrast'
        );
    }

    if (fieldKey === 'prioridad') {
        return (
            {
                low: 'success',
                medium: 'warning',
                high: 'danger'
            }[value] ?? 'contrast'
        );
    }

    return 'contrast';
}

function resetFilters() {
    state.filters = Object.fromEntries(filterFields.value.map((field) => [field.key, '']));
}

function resetForm() {
    state.form = {
        ...Object.fromEntries(
            formFields.value.map((field) => {
                const fallback = field.type === 'select' ? (field.options?.[0]?.value ?? '') : '';
                return [field.key, fallback];
            })
        ),
        custom_fields: Object.fromEntries(customFields.value.map((field) => [field.key, '']))
    };
}

async function loadResources() {
    state.loadingResources = true;

    try {
        const response = await api.get('/v1/data/resources');
        state.resources = response.data.datos ?? [];

        if (!state.selectedResourceKey || !state.resources.some((resource) => resource.key === state.selectedResourceKey)) {
            state.selectedResourceKey = state.resources[0]?.key ?? null;
        }
    } finally {
        state.loadingResources = false;
    }
}

async function loadRecords() {
    if (!state.selectedResourceKey) {
        state.records = [];
        state.total = 0;
        return;
    }

    state.loadingRecords = true;

    try {
        const filters = Object.fromEntries(Object.entries(state.filters).filter(([, value]) => value));
        const response = await api.get(`/v1/data/${state.selectedResourceKey}`, {
            params: {
                page: state.page,
                per_page: state.perPage,
                q: state.search || undefined,
                sort_by: state.sortBy || undefined,
                sort_direction: state.sortDirection || undefined,
                filters
            }
        });

        state.records = response.data.datos ?? [];
        state.total = response.data.meta?.pagination?.total ?? 0;
    } finally {
        state.loadingRecords = false;
    }
}

async function loadTransfers() {
    if (!state.selectedResourceKey) {
        state.transferRuns = [];
        return;
    }

    state.loadingTransfers = true;

    try {
        const response = await api.get(`/v1/data/${state.selectedResourceKey}/transfers`);
        state.transferRuns = response.data.datos ?? [];
    } finally {
        state.loadingTransfers = false;
    }
}

async function loadRelationOptions() {
    const fields = formFields.value.filter((field) => field.type === 'relation' && field.relation?.resource_key);

    state.relationOptions = {};

    await Promise.all(
        fields.map(async (field) => {
            try {
                const response = await api.get(`/v1/data/${field.relation.resource_key}`, {
                    params: {
                        per_page: 100
                    }
                });

                state.relationOptions[field.key] = (response.data.datos ?? []).map((record) => ({
                    label: record[field.relation.display_key ?? field.relation.label_field] ?? record[field.relation.label_field ?? 'nombre'],
                    value: record.id
                }));
            } catch {
                state.relationOptions[field.key] = [];
            }
        })
    );
}

function prepareResourceState() {
    const resource = currentResource.value;

    if (!resource) {
        resetFilters();
        state.sortBy = null;
        state.sortDirection = null;
        return;
    }

    state.perPage = resource.per_page_options?.[0] ?? 10;
    state.sortBy = resource.default_sort?.field ?? null;
    state.sortDirection = resource.default_sort?.direction ?? 'asc';
    state.page = 1;
    resetFilters();
    resetForm();
    state.transferRuns = [];
    state.relationOptions = {};
    state.visibleColumnKeys = [];
}

function openCreateDialog() {
    state.editingRecord = null;
    resetForm();
    state.dialogVisible = true;
}

function openEditDialog(record) {
    state.editingRecord = record;
    resetForm();

    formFields.value.forEach((field) => {
        state.form[field.key] = record[field.key] ?? '';
    });

    state.form.custom_fields = {
        ...state.form.custom_fields,
        ...(record.custom_fields ?? {})
    };

    state.dialogVisible = true;
}

async function saveRecord() {
    if (!state.selectedResourceKey) {
        return;
    }

    state.saving = true;

    try {
        if (state.editingRecord) {
            await api.patch(`/v1/data/${state.selectedResourceKey}/${state.editingRecord.id}`, state.form);
        } else {
            await api.post(`/v1/data/${state.selectedResourceKey}`, state.form);
        }

        state.dialogVisible = false;
        await loadRecords();

        toast.add({
            severity: 'success',
            summary: state.editingRecord ? 'Registro actualizado' : 'Registro creado',
            detail: 'El recurso del Data Engine fue guardado correctamente.',
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo guardar',
            detail: error?.response?.data?.mensaje ?? 'Revisa los datos enviados al recurso.',
            life: 4000
        });
    } finally {
        state.saving = false;
    }
}

function openImportDialog() {
    state.importFile = null;
    state.importDialogVisible = true;
}

function onImportFileChange(event) {
    state.importFile = event.target.files?.[0] ?? null;
}

async function exportResource() {
    if (!state.selectedResourceKey) {
        return;
    }

    state.exporting = true;

    try {
        const filters = Object.fromEntries(Object.entries(state.filters).filter(([, value]) => value));
        const response = await api.get(`/v1/data/${state.selectedResourceKey}/export`, {
            params: {
                q: state.search || undefined,
                sort_by: state.sortBy || undefined,
                sort_direction: state.sortDirection || undefined,
                filters
            },
            responseType: 'blob'
        });

        const disposition = response.headers['content-disposition'] ?? '';
        const match = disposition.match(/filename="?([^"]+)"?/i);
        const fileName = match?.[1] ?? `${state.selectedResourceKey}.csv`;
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'text/csv' }));
        const link = document.createElement('a');

        link.href = url;
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        await loadTransfers();

        toast.add({
            severity: 'success',
            summary: 'Exportacion completada',
            detail: 'El CSV del recurso actual se descargo correctamente.',
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo exportar',
            detail: error?.response?.data?.mensaje ?? 'No se pudo generar el CSV del recurso actual.',
            life: 4000
        });
    } finally {
        state.exporting = false;
    }
}

async function importResource() {
    if (!state.selectedResourceKey || !state.importFile) {
        return;
    }

    state.importing = true;

    try {
        const formData = new FormData();
        formData.append('file', state.importFile);

        const response = await api.post(`/v1/data/${state.selectedResourceKey}/import`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        state.importDialogVisible = false;
        state.importFile = null;

        if (importInput.value) {
            importInput.value.value = '';
        }

        state.page = 1;
        await Promise.all([loadRecords(), loadTransfers()]);

        toast.add({
            severity: response.data.datos?.records_failed > 0 ? 'warn' : 'success',
            summary: response.data.datos?.records_failed > 0 ? 'Importacion parcial' : 'Importacion completada',
            detail:
                response.data.datos?.records_failed > 0
                    ? `Se procesaron ${response.data.datos.records_processed} filas y ${response.data.datos.records_failed} quedaron con error.`
                    : `Se procesaron ${response.data.datos?.records_processed ?? 0} filas correctamente.`,
            life: 4000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo importar',
            detail: error?.response?.data?.errores?.file?.[0] ?? error?.response?.data?.mensaje ?? 'Revisa el CSV e intenta de nuevo.',
            life: 4500
        });
    } finally {
        state.importing = false;
    }
}

function confirmDelete(record) {
    confirm.require({
        message: `Se eliminara "${record.nombre ?? `#${record.id}`}" del recurso actual.`,
        header: 'Eliminar registro',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: async () => {
            try {
                await api.delete(`/v1/data/${state.selectedResourceKey}/${record.id}`);

                if (state.records.length === 1 && state.page > 1) {
                    state.page -= 1;
                }

                await loadRecords();
                toast.add({
                    severity: 'success',
                    summary: 'Registro eliminado',
                    detail: 'El soft delete se aplico correctamente.',
                    life: 3000
                });
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'No se pudo eliminar',
                    detail: error?.response?.data?.mensaje ?? 'No se pudo eliminar el registro seleccionado.',
                    life: 4000
                });
            }
        }
    });
}

function applyFilters() {
    state.page = 1;
    loadRecords();
}

function clearSearch() {
    state.search = '';
    state.page = 1;
    loadRecords();
}

async function onVisibleColumnsChange(value) {
    state.visibleColumnKeys = value.length ? value : defaultVisibleColumnsForCurrentResource();
    await saveViewPreferences();
}

function onPageChange(event) {
    state.page = Math.floor(event.first / event.rows) + 1;
    state.perPage = event.rows;
    loadRecords();
}

function onSort(event) {
    state.sortBy = event.sortField;
    state.sortDirection = event.sortOrder === 1 ? 'asc' : 'desc';
    loadRecords();
}

function relationOptionsForField(field) {
    return state.relationOptions[field.key] ?? [];
}

async function openModuleAdmin() {
    await moduleCatalog.loadModules(true);
    router.push('/admin/modules');
}

watch(
    () => state.selectedResourceKey,
    async () => {
        prepareResourceState();
        syncVisibleColumnsFromPreferences();
        await Promise.all([loadRelationOptions(), loadRecords(), loadTransfers()]);
    }
);

onMounted(async () => {
    await settingsStore.initialize();
    await moduleCatalog.loadModules();
    await loadResources();
});
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Core Platform</div>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-slate-900 mb-3">Data Engine</h1>
                    <p class="text-slate-600 max-w-3xl">El core ya expone un CRUD universal tenant-aware con busqueda, filtros, ordenamiento, paginacion y soft delete. Este primer recurso demo valida el contrato que usaran los modulos futuros.</p>
                </div>
                <div class="flex gap-3">
                    <Tag severity="info" :value="hasResources ? `${resources.length} recurso${resources.length === 1 ? '' : 's'}` : 'Sin recursos'" />
                </div>
            </div>
        </div>

        <StateEmpty
            v-if="!hasResources"
            title="No hay recursos disponibles"
            description="El Data Engine ya esta activo, pero por ahora el recurso demo vive dentro de Demo Module. Si el modulo esta deshabilitado, aqui no apareceran recursos."
            actionLabel="Ir a administracion de modulos"
            icon="pi pi-database"
            @action="openModuleAdmin"
        />

        <div v-else class="space-y-6">
            <StateSkeleton v-if="state.loadingResources" />

            <div v-else class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-4">
                        <label class="block text-sm font-semibold text-slate-600 mb-2">Recurso</label>
                        <Select v-model="state.selectedResourceKey" :options="state.resources" optionLabel="name" optionValue="key" placeholder="Selecciona un recurso" class="w-full" :loading="state.loadingResources" />
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <label class="block text-sm font-semibold text-slate-600 mb-2">Busqueda global</label>
                        <IconField>
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="state.search" class="w-full" placeholder="Buscar por nombre, empresa, email..." @keyup.enter="applyFilters" />
                        </IconField>
                    </div>
                    <div class="col-span-12 md:col-span-4 flex items-end gap-3">
                        <Button label="Buscar" icon="pi pi-search" @click="applyFilters" />
                        <Button label="Limpiar" severity="secondary" outlined @click="clearSearch" />
                        <Button v-if="resourceCapabilities.import" label="Importar CSV" icon="pi pi-upload" severity="secondary" outlined @click="openImportDialog" />
                        <Button v-if="resourceCapabilities.export" label="Exportar CSV" icon="pi pi-download" severity="secondary" :loading="state.exporting" @click="exportResource" />
                        <Button v-if="resourceCapabilities.create" label="Nuevo registro" icon="pi pi-plus" @click="openCreateDialog" />
                    </div>
                </div>

                <div v-if="currentResource" class="mt-4 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-5">
                        <label class="block text-sm font-semibold text-slate-600 mb-2">Columnas visibles</label>
                        <MultiSelect :modelValue="state.visibleColumnKeys" :options="tableFields" optionLabel="label" optionValue="key" display="chip" class="w-full" placeholder="Selecciona columnas" @update:modelValue="onVisibleColumnsChange" />
                    </div>
                </div>

                <div v-if="currentResource" class="mt-5 flex flex-wrap gap-4 items-start">
                    <div v-for="field in filterFields" :key="field.key" class="w-full md:w-56">
                        <label class="block text-sm font-semibold text-slate-600 mb-2">{{ field.label }}</label>
                        <Select v-model="state.filters[field.key]" :options="field.options" optionLabel="label" optionValue="value" showClear class="w-full" @change="applyFilters" />
                    </div>
                    <div class="flex-1 min-w-56 rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3">
                        <div class="text-sm font-semibold text-slate-700 mb-1">{{ currentResource.name }}</div>
                        <p class="text-sm text-slate-600 m-0">{{ currentResource.description }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <DataTable
                    :value="state.records"
                    dataKey="id"
                    :size="tableSize"
                    lazy
                    paginator
                    :rows="state.perPage"
                    :first="pageFirst"
                    :totalRecords="state.total"
                    :rowsPerPageOptions="perPageOptions"
                    :loading="state.loadingRecords"
                    :sortField="state.sortBy"
                    :sortOrder="state.sortDirection === 'asc' ? 1 : -1"
                    @page="onPageChange"
                    @sort="onSort"
                >
                    <Column v-for="field in visibleTableFields" :key="field.key" :field="field.key" :header="field.label" :sortable="field.sortable" style="min-width: 12rem">
                        <template #body="slotProps">
                            <Tag v-if="field.type === 'select'" :severity="tagSeverity(field.key, slotProps.data[field.key])" :value="humanizeValue(field, slotProps.data[field.key], slotProps.data[field.relation?.display_key])" />
                            <span v-else>{{ humanizeValue(field, slotProps.data[field.key], slotProps.data[field.relation?.display_key]) }}</span>
                        </template>
                    </Column>
                    <Column header="Acciones" style="width: 10rem">
                        <template #body="slotProps">
                            <div class="flex gap-2">
                                <Button icon="pi pi-pencil" severity="secondary" text rounded @click="openEditDialog(slotProps.data)" />
                                <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(slotProps.data)" />
                            </div>
                        </template>
                    </Column>
                    <template #empty>
                        <StateEmpty title="Sin registros" description="No hay registros para esta combinacion de busqueda y filtros." icon="pi pi-search" />
                    </template>
                </DataTable>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900 mb-1">Historial de transferencias</h2>
                        <p class="text-sm text-slate-600 m-0">Cada exportacion e importacion queda registrada por tenant para auditar y depurar el recurso actual.</p>
                    </div>
                    <Tag severity="contrast" :value="`${state.transferRuns.length} corrida${state.transferRuns.length === 1 ? '' : 's'}`" />
                </div>

                <DataTable :value="state.transferRuns" dataKey="id" :loading="state.loadingTransfers">
                    <Column field="type" header="Tipo" style="min-width: 8rem">
                        <template #body="slotProps">
                            <Tag :severity="slotProps.data.type === 'export' ? 'info' : 'warning'" :value="slotProps.data.type === 'export' ? 'Export' : 'Import'" />
                        </template>
                    </Column>
                    <Column field="status" header="Estado" style="min-width: 12rem">
                        <template #body="slotProps">
                            <Tag
                                :severity="
                                    {
                                        completed: 'success',
                                        completed_with_errors: 'warning',
                                        failed: 'danger',
                                        processing: 'info'
                                    }[slotProps.data.status] ?? 'contrast'
                                "
                                :value="slotProps.data.status"
                            />
                        </template>
                    </Column>
                    <Column field="file_name" header="Archivo" style="min-width: 14rem" />
                    <Column field="records_processed" header="Procesados" style="min-width: 9rem" />
                    <Column field="records_failed" header="Errores" style="min-width: 8rem" />
                    <Column field="created_at" header="Creado" style="min-width: 14rem">
                        <template #body="slotProps">
                            {{ slotProps.data.created_at ? formatDateTime(slotProps.data.created_at) : 'Sin dato' }}
                        </template>
                    </Column>
                    <Column field="error_summary" header="Resumen" style="min-width: 18rem">
                        <template #body="slotProps">
                            {{ slotProps.data.error_summary || 'Sin errores relevantes' }}
                        </template>
                    </Column>
                    <template #empty>
                        <StateEmpty title="Sin transferencias" description="Todavia no hay transferencias registradas para este recurso." icon="pi pi-download" />
                    </template>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="state.dialogVisible" modal :header="state.editingRecord ? 'Editar registro' : 'Nuevo registro'" :style="{ width: '42rem' }">
            <div class="grid grid-cols-12 gap-4">
                <div v-for="field in formFields" :key="field.key" class="col-span-12" :class="field.type === 'textarea' ? '' : 'md:col-span-6'">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">{{ field.label }}</label>
                    <Select v-if="field.type === 'select'" v-model="state.form[field.key]" :options="field.options" optionLabel="label" optionValue="value" class="w-full" />
                    <Select v-else-if="field.type === 'relation'" v-model="state.form[field.key]" :options="relationOptionsForField(field)" optionLabel="label" optionValue="value" showClear class="w-full" />
                    <Textarea v-else-if="field.type === 'textarea'" v-model="state.form[field.key]" rows="4" class="w-full" autoResize />
                    <InputText v-else v-model="state.form[field.key]" class="w-full" :type="field.type === 'email' ? 'email' : 'text'" />
                </div>
                <div v-if="customFields.length" class="col-span-12 pt-2">
                    <div class="text-sm uppercase tracking-[0.2em] text-slate-500 font-semibold mb-3">Custom Fields</div>
                </div>
                <div v-for="field in customFields" :key="field.key" class="col-span-12 md:col-span-6">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">{{ field.label }}</label>
                    <Select v-if="field.type === 'select'" v-model="state.form.custom_fields[field.key]" :options="field.options" optionLabel="label" optionValue="value" showClear class="w-full" />
                    <InputText v-else v-model="state.form.custom_fields[field.key]" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" severity="secondary" outlined @click="state.dialogVisible = false" />
                <Button :label="state.editingRecord ? 'Guardar cambios' : 'Crear registro'" :loading="state.saving" @click="saveRecord" />
            </template>
        </Dialog>

        <Dialog v-model:visible="state.importDialogVisible" modal header="Importar CSV" :style="{ width: '34rem' }">
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    Usa un archivo CSV con encabezados iguales a las claves del recurso. Para `Demo Contacts` puedes usar: `nombre,email,telefono,empresa,estado,prioridad,notas`.
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Archivo CSV</label>
                    <input ref="importInput" type="file" accept=".csv,text/csv" class="block w-full text-sm text-slate-600" @change="onImportFileChange" />
                    <div class="mt-2 text-sm text-slate-500">
                        {{ state.importFile ? `Seleccionado: ${state.importFile.name}` : 'Todavia no seleccionaste un archivo.' }}
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" severity="secondary" outlined @click="state.importDialogVisible = false" />
                <Button label="Importar" icon="pi pi-upload" :disabled="!state.importFile" :loading="state.importing" @click="importResource" />
            </template>
        </Dialog>
    </div>
</template>
