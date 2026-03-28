<script setup>
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import api from '@/service/api';
import { computed, onMounted, reactive } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const state = reactive({
    loading: false,
    saving: false,
    items: [],
    availablePermissions: [],
    showDialog: false,
    form: defaultForm(),
    errorMessage: ''
});

const roles = computed(() => state.items);
const hasRoles = computed(() => state.items.length > 0);
const isEditing = computed(() => Boolean(state.form.id));
const permissionOptions = computed(() =>
    state.availablePermissions.map((permission) => ({
        label: permission,
        value: permission
    }))
);

function defaultForm() {
    return {
        id: null,
        name: '',
        permissions: []
    };
}

async function loadRoles() {
    state.loading = true;

    try {
        const response = await api.get('/v1/roles');
        state.items = response.data.datos ?? [];
        state.availablePermissions = response.data.meta?.available_permissions ?? [];
    } finally {
        state.loading = false;
    }
}

function openCreateRole() {
    state.form = defaultForm();
    state.errorMessage = '';
    state.showDialog = true;
}

function openEditRole(role) {
    state.form = {
        id: role.id,
        name: role.name,
        permissions: [...(role.permissions ?? [])]
    };
    state.errorMessage = '';
    state.showDialog = true;
}

function closeDialog() {
    state.form = defaultForm();
    state.errorMessage = '';
    state.showDialog = false;
}

async function submitRole() {
    if (state.saving) {
        return;
    }

    state.saving = true;
    state.errorMessage = '';

    try {
        const payload = {
            name: state.form.name,
            permissions: state.form.permissions
        };

        if (isEditing.value) {
            await api.patch(`/v1/roles/${state.form.id}`, payload);
        } else {
            await api.post('/v1/roles', payload);
        }

        await loadRoles();
        closeDialog();
        toast.add({
            severity: 'success',
            summary: isEditing.value ? 'Rol actualizado' : 'Rol creado',
            detail: isEditing.value ? 'Los permisos del rol se actualizaron correctamente.' : 'El rol fue creado correctamente.',
            life: 3000
        });
    } catch (error) {
        state.errorMessage = error?.response?.data?.mensaje ?? 'No se pudo guardar el rol.';
        toast.add({
            severity: 'error',
            summary: 'No se pudo guardar el rol',
            detail: state.errorMessage,
            life: 4000
        });
    } finally {
        state.saving = false;
    }
}

onMounted(loadRoles);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Administration</div>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-slate-900 mb-3">Roles y permisos</h1>
                    <p class="text-slate-600 max-w-3xl">Administra los roles reutilizables del stack y define que permisos concretos recibe cada uno. Luego esos roles pueden asignarse a usuarios desde la gestion de accesos.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Tag severity="info" :value="`${roles.length} rol${roles.length === 1 ? '' : 'es'}`" />
                    <Button label="Nuevo rol" icon="pi pi-plus" @click="openCreateRole" />
                </div>
            </div>
        </div>

        <StateSkeleton v-if="state.loading" />

        <div v-else class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <StateEmpty v-if="!hasRoles" title="No hay roles configurados" description="Crea un rol para empezar a agrupar permisos reutilizables del sistema." icon="pi pi-shield" />

            <DataTable v-else :value="roles" dataKey="id">
                <Column field="name" header="Rol" style="min-width: 14rem" />
                <Column header="Permisos" style="min-width: 28rem">
                    <template #body="slotProps">
                        <div class="flex flex-wrap gap-2">
                            <Tag v-for="permission in slotProps.data.permissions" :key="permission" :value="permission" severity="secondary" />
                            <span v-if="!(slotProps.data.permissions ?? []).length" class="text-color-secondary">Sin permisos asignados</span>
                        </div>
                    </template>
                </Column>
                <Column header="Acciones" style="min-width: 10rem">
                    <template #body="slotProps">
                        <Button label="Editar" icon="pi pi-pencil" severity="secondary" size="small" @click="openEditRole(slotProps.data)" />
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="state.showDialog" modal :header="isEditing ? 'Editar rol' : 'Nuevo rol'" class="w-full max-w-3xl">
            <div class="app-form-section">
                <div class="app-form-section-header">
                    <div class="app-form-section-title">Definicion del rol</div>
                    <p class="app-form-section-description">Asigna un nombre claro y vincula permisos del catalogo para controlar acceso y acciones del shell.</p>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-slate-700">Nombre del rol</label>
                    <InputText v-model="state.form.name" :disabled="state.saving" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-slate-700">Permisos</label>
                    <MultiSelect v-model="state.form.permissions" :options="permissionOptions" optionLabel="label" optionValue="value" display="chip" filter class="w-full" placeholder="Selecciona permisos" :disabled="state.saving" />
                </div>
                <Message v-if="state.errorMessage" severity="error" :closable="false">{{ state.errorMessage }}</Message>
            </div>

            <template #footer>
                <div class="app-dialog-footer">
                    <Button class="app-button-standard" label="Cancelar" severity="secondary" text @click="closeDialog" />
                    <Button class="app-button-standard" :label="isEditing ? 'Guardar cambios' : 'Crear rol'" icon="pi pi-save" :loading="state.saving" :disabled="state.saving" @click="submitRole" />
                </div>
            </template>
        </Dialog>
    </div>
</template>
