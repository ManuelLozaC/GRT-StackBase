<script setup>
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import api from '@/service/api';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, watch } from 'vue';
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
    editingRecord: null,
    form: {}
});

const currentResource = computed(() => state.resources.find((resource) => resource.key === state.selectedResourceKey) ?? null);
const resources = computed(() => state.resources);
const tableFields = computed(() => currentResource.value?.table_fields ?? []);
const formFields = computed(() => currentResource.value?.form_fields ?? []);
const filterFields = computed(() => currentResource.value?.filter_fields ?? []);
const perPageOptions = computed(() => currentResource.value?.per_page_options ?? [10, 25, 50]);
const demoModuleEnabled = computed(() => moduleCatalog.isModuleEnabled('demo-platform'));
const hasResources = computed(() => state.resources.length > 0);
const pageFirst = computed(() => (state.page - 1) * state.perPage);

function humanizeValue(field, value) {
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
    state.form = Object.fromEntries(
        formFields.value.map((field) => {
            const fallback = field.type === 'select' ? (field.options?.[0]?.value ?? '') : '';
            return [field.key, fallback];
        })
    );
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

async function openModuleAdmin() {
    await moduleCatalog.loadModules(true);
    router.push('/admin/modules');
}

watch(
    () => state.selectedResourceKey,
    async () => {
        prepareResourceState();
        await loadRecords();
    }
);

onMounted(async () => {
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

        <div v-if="!hasResources" class="rounded-3xl border border-dashed border-sky-300 bg-sky-50 p-8 text-sky-900">
            <div class="text-xl font-semibold mb-3">No hay recursos disponibles</div>
            <p class="mb-4">El `Data Engine` ya esta activo, pero por ahora el recurso demo vive dentro de `Demo Module`. Si el modulo esta deshabilitado, aqui no apareceran recursos.</p>
            <div class="flex flex-wrap gap-3 items-center">
                <Tag :severity="demoModuleEnabled ? 'success' : 'warning'" :value="demoModuleEnabled ? 'Demo Module activo' : 'Demo Module inactivo'" />
                <Button label="Ir a administracion de modulos" icon="pi pi-cog" @click="openModuleAdmin" />
            </div>
        </div>

        <div v-else class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
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
                        <Button label="Nuevo registro" icon="pi pi-plus" @click="openCreateDialog" />
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
                    <Column v-for="field in tableFields" :key="field.key" :field="field.key" :header="field.label" :sortable="field.sortable" style="min-width: 12rem">
                        <template #body="slotProps">
                            <Tag v-if="field.type === 'select'" :severity="tagSeverity(field.key, slotProps.data[field.key])" :value="humanizeValue(field, slotProps.data[field.key])" />
                            <span v-else>{{ humanizeValue(field, slotProps.data[field.key]) }}</span>
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
                        <div class="py-10 text-center text-slate-500">No hay registros para esta combinacion de busqueda y filtros.</div>
                    </template>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="state.dialogVisible" modal :header="state.editingRecord ? 'Editar registro' : 'Nuevo registro'" :style="{ width: '42rem' }">
            <div class="grid grid-cols-12 gap-4">
                <div v-for="field in formFields" :key="field.key" class="col-span-12" :class="field.type === 'textarea' ? '' : 'md:col-span-6'">
                    <label class="block text-sm font-semibold text-slate-600 mb-2">{{ field.label }}</label>
                    <Select v-if="field.type === 'select'" v-model="state.form[field.key]" :options="field.options" optionLabel="label" optionValue="value" class="w-full" />
                    <Textarea v-else-if="field.type === 'textarea'" v-model="state.form[field.key]" rows="4" class="w-full" autoResize />
                    <InputText v-else v-model="state.form[field.key]" class="w-full" :type="field.type === 'email' ? 'email' : 'text'" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" severity="secondary" outlined @click="state.dialogVisible = false" />
                <Button :label="state.editingRecord ? 'Guardar cambios' : 'Crear registro'" :loading="state.saving" @click="saveRecord" />
            </template>
        </Dialog>
    </div>
</template>
