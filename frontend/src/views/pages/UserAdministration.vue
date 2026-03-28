<script setup>
import { sessionStore } from '@/core/auth/sessionStore';
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import api from '@/service/api';
import { computed, onMounted, reactive, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();
const state = reactive({
    loading: false,
    savingUserId: null,
    impersonatingUserId: null,
    togglingUserId: null,
    savingForm: false,
    resettingPassword: false,
    showUserDialog: false,
    showPasswordDialog: false,
    items: [],
    availableRoles: [],
    availablePersonas: [],
    formError: '',
    form: defaultUserForm(),
    passwordForm: defaultPasswordForm()
});

const users = computed(() => state.items);
const hasUsers = computed(() => state.items.length > 0);
const isEditing = computed(() => Boolean(state.form.id));
const personaOptions = computed(() => state.availablePersonas);
const selectedPersona = computed(() => state.availablePersonas.find((persona) => persona.value === state.form.persona_id) ?? null);

function defaultUserForm() {
    return {
        id: null,
        persona_id: null,
        name: '',
        alias: '',
        email: '',
        activo: true,
        roles: [],
        password: '',
        password_confirmation: ''
    };
}

function defaultPasswordForm() {
    return {
        userId: null,
        userName: '',
        password: '',
        password_confirmation: ''
    };
}

function normalizeUser(user) {
    return {
        ...user,
        roleDraft: [...(user.roles ?? [])]
    };
}

async function loadUsers() {
    state.loading = true;

    try {
        const response = await api.get('/v1/users');
        state.items = (response.data.datos ?? []).map(normalizeUser);
        state.availableRoles = (response.data.meta?.available_roles ?? []).map((role) => ({
            label: role,
            value: role
        }));
        state.availablePersonas = (response.data.meta?.available_personas ?? []).map((persona) => ({
            label: persona.label,
            value: persona.id,
            correo: persona.correo,
            telefono: persona.telefono
        }));
    } finally {
        state.loading = false;
    }
}

function openCreateUser() {
    state.form = defaultUserForm();
    state.formError = '';
    state.showUserDialog = true;
}

function openEditUser(user) {
    state.form = {
        id: user.id,
        persona_id: user.persona?.id ?? null,
        name: user.name ?? '',
        alias: user.alias ?? '',
        email: user.email ?? '',
        telefono: user.telefono ?? '',
        activo: Boolean(user.activo),
        roles: [...(user.roles ?? [])],
        password: '',
        password_confirmation: ''
    };
    state.formError = '';
    state.showUserDialog = true;
}

function closeUserDialog() {
    state.showUserDialog = false;
    state.formError = '';
    state.form = defaultUserForm();
}

function syncPersonaIntoForm(personaId) {
    const persona = state.availablePersonas.find((candidate) => candidate.value === personaId) ?? null;

    if (!persona) {
        return;
    }

    state.form.name = persona.label;
    state.form.telefono = persona.telefono || '';

    if (!state.form.email) {
        state.form.email = persona.correo || '';
    }
}

watch(
    () => state.form.persona_id,
    (personaId) => {
        if (!personaId) {
            return;
        }

        syncPersonaIntoForm(personaId);
    }
);

async function submitUserForm() {
    if (state.savingForm) {
        return;
    }

    state.savingForm = true;
    state.formError = '';

    try {
        const payload = {
            persona_id: state.form.persona_id || null,
            name: state.form.persona_id ? null : state.form.name,
            alias: state.form.alias || null,
            email: state.form.email || null,
            activo: state.form.activo,
            roles: state.form.roles
        };

        if (isEditing.value) {
            await api.patch(`/v1/users/${state.form.id}`, payload);
        } else {
            await api.post('/v1/users', {
                ...payload,
                password: state.form.password,
                password_confirmation: state.form.password_confirmation
            });
        }

        await loadUsers();
        closeUserDialog();
        toast.add({
            severity: 'success',
            summary: isEditing.value ? 'Usuario actualizado' : 'Usuario creado',
            detail: isEditing.value ? 'Los cambios del usuario se guardaron correctamente.' : 'El nuevo usuario fue creado correctamente.',
            life: 3000
        });
    } catch (error) {
        state.formError = error?.response?.data?.mensaje ?? 'Revisa los datos e intenta de nuevo.';
        toast.add({
            severity: 'error',
            summary: 'No se pudo guardar el usuario',
            detail: state.formError,
            life: 4000
        });
    } finally {
        state.savingForm = false;
    }
}

async function saveRoles(user) {
    state.savingUserId = user.id;

    try {
        const response = await api.patch(`/v1/users/${user.id}/roles`, {
            roles: user.roleDraft
        });
        const updated = response.data.datos;
        const index = state.items.findIndex((item) => item.id === user.id);

        if (index >= 0) {
            state.items[index] = normalizeUser(updated);
        }

        toast.add({
            severity: 'success',
            summary: 'Roles actualizados',
            detail: `Los roles de ${updated.name} se guardaron correctamente.`,
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudieron guardar los roles',
            detail: error?.response?.data?.mensaje ?? 'Revisa la seleccion e intenta de nuevo.',
            life: 4000
        });
    } finally {
        state.savingUserId = null;
    }
}

async function toggleUserStatus(user) {
    state.togglingUserId = user.id;

    try {
        const response = await api.patch(`/v1/users/${user.id}/status`, {
            activo: !user.activo
        });
        const updated = response.data.datos;
        const index = state.items.findIndex((item) => item.id === user.id);

        if (index >= 0) {
            state.items[index] = normalizeUser(updated);
        }

        toast.add({
            severity: 'success',
            summary: updated.activo ? 'Usuario activado' : 'Usuario desactivado',
            detail: `${updated.name} ahora tiene estado ${updated.activo ? 'activo' : 'inactivo'}.`,
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo cambiar el estado',
            detail: error?.response?.data?.mensaje ?? 'Intenta nuevamente.',
            life: 4000
        });
    } finally {
        state.togglingUserId = null;
    }
}

function openPasswordDialog(user) {
    state.passwordForm = {
        userId: user.id,
        userName: user.name,
        password: '',
        password_confirmation: ''
    };
    state.showPasswordDialog = true;
}

function closePasswordDialog() {
    state.showPasswordDialog = false;
    state.passwordForm = defaultPasswordForm();
}

async function submitPasswordReset() {
    state.resettingPassword = true;

    try {
        await api.post(`/v1/users/${state.passwordForm.userId}/reset-password`, {
            password: state.passwordForm.password,
            password_confirmation: state.passwordForm.password_confirmation
        });
        await loadUsers();
        closePasswordDialog();
        toast.add({
            severity: 'success',
            summary: 'Contrasena restablecida',
            detail: 'El usuario debera cambiarla al volver a ingresar.',
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo restablecer la contrasena',
            detail: error?.response?.data?.mensaje ?? 'Revisa la nueva contrasena e intenta de nuevo.',
            life: 4000
        });
    } finally {
        state.resettingPassword = false;
    }
}

async function impersonateUser(user) {
    state.impersonatingUserId = user.id;

    try {
        await sessionStore.impersonate(user.id);
        toast.add({
            severity: 'success',
            summary: 'Impersonacion iniciada',
            detail: `La sesion ahora esta actuando como ${user.email}.`,
            life: 3000
        });
        await router.push({ name: 'dashboard' });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo impersonar',
            detail: error?.response?.data?.mensaje ?? 'No se pudo abrir la sesion impersonada.',
            life: 4000
        });
    } finally {
        state.impersonatingUserId = null;
    }
}

onMounted(loadUsers);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Administration</div>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-slate-900 mb-3">Usuarios y acceso operativo</h1>
                    <p class="text-slate-600 max-w-3xl">Desde aqui puedes crear usuarios, vincular personas, gestionar multi-rol, activar o desactivar accesos, restablecer contrasenas e impersonar miembros de la organizacion activa.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Tag severity="info" :value="`${users.length} usuario${users.length === 1 ? '' : 's'}`" />
                    <Button label="Nuevo usuario" icon="pi pi-plus" @click="openCreateUser" />
                </div>
            </div>
        </div>

        <StateSkeleton v-if="state.loading" />

        <div v-else class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <StateEmpty v-if="!hasUsers" title="No hay usuarios en la organizacion activa" description="Cuando existan miembros dentro del tenant actual, aqui podras gestionarlos, cambiar sus roles e impersonarlos para soporte." />

            <DataTable v-else :value="users" dataKey="id">
                <Column field="name" header="Usuario" style="min-width: 17rem">
                    <template #body="slotProps">
                        <div class="flex flex-col gap-1">
                            <strong>{{ slotProps.data.name }}</strong>
                            <small class="text-color-secondary">{{ slotProps.data.alias || slotProps.data.email }}</small>
                            <small class="text-color-secondary">{{ slotProps.data.email }}</small>
                        </div>
                    </template>
                </Column>
                <Column header="Persona" style="min-width: 14rem">
                    <template #body="slotProps">
                        <div class="flex flex-col gap-1">
                            <span>{{ slotProps.data.persona?.nombre || 'Sin vincular' }}</span>
                            <small class="text-color-secondary">{{ slotProps.data.persona?.correo || 'Sin correo de persona' }}</small>
                        </div>
                    </template>
                </Column>
                <Column field="organizacion_activa.nombre" header="Organizacion activa" style="min-width: 13rem" />
                <Column header="Estado" style="min-width: 10rem">
                    <template #body="slotProps">
                        <Tag :severity="slotProps.data.activo ? 'success' : 'danger'" :value="slotProps.data.activo ? 'Activo' : 'Inactivo'" />
                    </template>
                </Column>
                <Column header="Roles" style="min-width: 18rem">
                    <template #body="slotProps">
                        <MultiSelect v-model="slotProps.data.roleDraft" :options="state.availableRoles" optionLabel="label" optionValue="value" display="chip" class="w-full" placeholder="Selecciona roles" />
                    </template>
                </Column>
                <Column header="Acciones" style="min-width: 24rem">
                    <template #body="slotProps">
                        <div class="flex flex-wrap gap-2">
                            <Button label="Guardar roles" icon="pi pi-save" size="small" severity="secondary" :loading="state.savingUserId === slotProps.data.id" @click="saveRoles(slotProps.data)" />
                            <Button label="Editar" icon="pi pi-pencil" size="small" severity="secondary" @click="openEditUser(slotProps.data)" />
                            <Button
                                :label="slotProps.data.activo ? 'Desactivar' : 'Activar'"
                                :icon="slotProps.data.activo ? 'pi pi-ban' : 'pi pi-check'"
                                size="small"
                                severity="secondary"
                                :loading="state.togglingUserId === slotProps.data.id"
                                @click="toggleUserStatus(slotProps.data)"
                            />
                            <Button label="Reset password" icon="pi pi-key" size="small" severity="secondary" @click="openPasswordDialog(slotProps.data)" />
                            <Button
                                label="Impersonar"
                                icon="pi pi-user-edit"
                                size="small"
                                :disabled="sessionStore.state.user?.id === slotProps.data.id"
                                :loading="state.impersonatingUserId === slotProps.data.id"
                                @click="impersonateUser(slotProps.data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="state.showUserDialog" modal :header="isEditing ? 'Editar usuario' : 'Nuevo usuario'" class="w-full max-w-3xl">
            <div class="app-form-section">
                <div class="app-form-section-header">
                    <div class="app-form-section-title">Datos de acceso</div>
                    <p class="app-form-section-description">Configura persona, alias, correo, roles y estado del usuario dentro de la empresa activa.</p>
                </div>
                <div class="app-form-grid md:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-slate-700">Persona</label>
                        <Select v-model="state.form.persona_id" :options="personaOptions" optionLabel="label" optionValue="value" showClear placeholder="Selecciona una persona" class="w-full" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-slate-700">Alias</label>
                        <InputText v-model="state.form.alias" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-slate-700">Correo de acceso</label>
                        <InputText v-model="state.form.email" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-slate-700">Roles</label>
                        <MultiSelect v-model="state.form.roles" :options="state.availableRoles" optionLabel="label" optionValue="value" display="chip" placeholder="Selecciona roles" class="w-full" />
                    </div>
                    <div v-if="selectedPersona" class="md:col-span-2 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                        <div class="font-semibold mb-1">Datos heredados desde persona</div>
                        <div>
                            Nombre: <strong>{{ selectedPersona.label }}</strong>
                        </div>
                        <div>
                            Correo registrado: <strong>{{ selectedPersona.correo || 'Sin correo en persona' }}</strong>
                        </div>
                        <div>
                            Telefono: <strong>{{ selectedPersona.telefono || 'Sin telefono en persona' }}</strong>
                        </div>
                    </div>
                    <div v-else class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-sm font-medium text-slate-700">Nombre de usuario</label>
                        <InputText v-model="state.form.name" />
                    </div>
                    <div v-if="!isEditing" class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-slate-700">Contrasena</label>
                        <Password v-model="state.form.password" toggleMask :feedback="false" fluid />
                    </div>
                    <div v-if="!isEditing" class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-slate-700">Confirmar contrasena</label>
                        <Password v-model="state.form.password_confirmation" toggleMask :feedback="false" fluid />
                    </div>
                    <div class="flex items-center gap-3 md:col-span-2">
                        <ToggleSwitch v-model="state.form.activo" />
                        <span class="text-sm text-slate-700">Usuario activo</span>
                    </div>
                    <div class="md:col-span-2">
                        <Message v-if="state.formError" severity="error" :closable="false">{{ state.formError }}</Message>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="app-dialog-footer">
                    <Button class="app-button-standard" label="Cancelar" severity="secondary" text @click="closeUserDialog" />
                    <Button class="app-button-standard" :label="isEditing ? 'Guardar cambios' : 'Crear usuario'" icon="pi pi-save" :loading="state.savingForm" @click="submitUserForm" />
                </div>
            </template>
        </Dialog>

        <Dialog v-model:visible="state.showPasswordDialog" modal header="Restablecer contrasena" class="w-full max-w-xl">
            <div class="app-form-section">
                <div class="app-form-section-header">
                    <div class="app-form-section-title">Nueva contrasena</div>
                    <p class="app-form-section-description">Este cambio invalida la contrasena anterior y obliga al usuario a usar la nueva al volver a ingresar.</p>
                </div>
                <p class="text-slate-600">
                    Define una nueva contrasena para <strong>{{ state.passwordForm.userName }}</strong
                    >. El usuario debera cambiarla al volver a ingresar.
                </p>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-slate-700">Nueva contrasena</label>
                    <Password v-model="state.passwordForm.password" toggleMask :feedback="false" fluid />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-slate-700">Confirmar contrasena</label>
                    <Password v-model="state.passwordForm.password_confirmation" toggleMask :feedback="false" fluid />
                </div>
            </div>

            <template #footer>
                <div class="app-dialog-footer">
                    <Button class="app-button-standard" label="Cancelar" severity="secondary" text @click="closePasswordDialog" />
                    <Button class="app-button-standard" label="Restablecer" icon="pi pi-key" :loading="state.resettingPassword" @click="submitPasswordReset" />
                </div>
            </template>
        </Dialog>
    </div>
</template>
