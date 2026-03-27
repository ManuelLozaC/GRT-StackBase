<script setup>
import { accessStore } from '@/core/auth/accessStore';
import { sessionStore } from '@/core/auth/sessionStore';
import { tenantStore } from '@/core/auth/tenantStore';
import { notificationStore } from '@/core/notifications/notificationStore';
import { useLayout } from '@/layout/composables/layout';
import { useToast } from 'primevue/usetoast';
import { computed, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const toast = useToast();
const { toggleMenu } = useLayout();

const organizations = computed(() => tenantStore.organizations.value);
const activeOrganizationId = computed(() => tenantStore.activeOrganization.value?.id ?? '');
const workAssignments = computed(() => tenantStore.availableWorkAssignments.value);
const activeWorkAssignmentId = computed(() => tenantStore.activeWorkAssignment.value?.id ?? '');
const unreadNotifications = computed(() => notificationStore.unreadCount.value);
const canManageSettings = computed(() => accessStore.hasPermission('settings.manage'));
const canManageModules = computed(() => accessStore.hasPermission('modules.manage'));
const impersonation = computed(() => sessionStore.state.user?.impersonation ?? { active: false, impersonated_by: null });

const accountMenuOpen = ref(false);
const contextModalVisible = ref(false);
const contextState = reactive({
    organizationId: '',
    workAssignmentId: ''
});

watch(
    [activeOrganizationId, activeWorkAssignmentId],
    ([organizationId, workAssignmentId]) => {
        contextState.organizationId = organizationId;
        contextState.workAssignmentId = workAssignmentId;
    },
    { immediate: true }
);

async function logout() {
    await sessionStore.logout();
    accountMenuOpen.value = false;

    toast.add({
        severity: 'success',
        summary: 'Sesion cerrada',
        detail: 'Hasta pronto.',
        life: 2500
    });

    await router.push({ name: 'login' });
}

async function openNotifications() {
    await notificationStore.loadNotifications();
    await router.push({ name: 'demo-notifications' });
}

function toggleAccountMenu() {
    accountMenuOpen.value = !accountMenuOpen.value;
}

function openContextModal() {
    accountMenuOpen.value = false;
    contextState.organizationId = activeOrganizationId.value;
    contextState.workAssignmentId = activeWorkAssignmentId.value;
    contextModalVisible.value = true;
}

async function applyOrganizationChange() {
    const selectedId = Number(contextState.organizationId);

    if (!selectedId || selectedId === activeOrganizationId.value) {
        return;
    }

    await tenantStore.switchActiveOrganization(selectedId);
    contextState.organizationId = tenantStore.activeOrganization.value?.id ?? '';
    contextState.workAssignmentId = tenantStore.activeWorkAssignment.value?.id ?? '';

    toast.add({
        severity: 'success',
        summary: 'Empresa actualizada',
        detail: 'Ahora puedes revisar o ajustar el contexto laboral de esa empresa.',
        life: 2600
    });
}

async function applyWorkAssignmentChange() {
    const selectedId = Number(contextState.workAssignmentId);

    if (!selectedId || selectedId === activeWorkAssignmentId.value) {
        contextModalVisible.value = false;
        return;
    }

    await tenantStore.switchActiveWorkAssignment(selectedId);
    contextModalVisible.value = false;

    toast.add({
        severity: 'success',
        summary: 'Contexto laboral actualizado',
        detail: 'La sucursal o funcion activa ya cambio para esta sesion.',
        life: 2500
    });
}

async function openRoute(name) {
    accountMenuOpen.value = false;
    await router.push({ name });
}

async function leaveImpersonation() {
    accountMenuOpen.value = false;
    await sessionStore.leaveImpersonation();

    toast.add({
        severity: 'success',
        summary: 'Impersonacion finalizada',
        detail: 'Se restauro la sesion del usuario original.',
        life: 3000
    });

    await router.push({ name: 'dashboard' });
}
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-logo-container">
            <button class="layout-menu-button layout-topbar-action" @click="toggleMenu">
                <i class="pi pi-bars"></i>
            </button>
            <router-link to="/" class="layout-topbar-logo">
                <span>GRT StackBase</span>
            </router-link>
        </div>

        <div class="layout-topbar-actions">
            <button type="button" class="layout-topbar-action topbar-bell-button" @click="openNotifications">
                <i class="pi pi-bell"></i>
                <span v-if="unreadNotifications > 0" class="topbar-bell-badge">{{ unreadNotifications }}</span>
            </button>

            <div class="topbar-account-wrapper">
                <button type="button" class="topbar-account-trigger" @click="toggleAccountMenu">
                    <div class="topbar-user-summary">
                        <div class="topbar-user-name">{{ sessionStore.state.user?.name || 'Usuario' }}</div>
                        <div class="topbar-user-email">{{ sessionStore.state.user?.email || 'Sin correo' }}</div>
                    </div>
                    <i class="pi pi-angle-down text-sm"></i>
                </button>

                <div v-if="accountMenuOpen" class="topbar-account-panel">
                    <div v-if="impersonation.active" class="topbar-impersonation-banner">
                        <div>
                            <strong>Impersonando sesion</strong>
                            <div>{{ impersonation.impersonated_by?.email || 'Admin original' }}</div>
                        </div>
                        <button type="button" class="topbar-impersonation-button" @click="leaveImpersonation">Salir</button>
                    </div>

                    <button type="button" class="topbar-action-panel-item" @click="openContextModal">
                        <i class="pi pi-building"></i>
                        <div>
                            <span>Empresa y contexto</span>
                            <small>{{ tenantStore.activeOrganization.value?.nombre || 'Sin empresa activa' }}</small>
                        </div>
                    </button>

                    <button type="button" class="topbar-action-panel-item" @click="openRoute('my-preferences')">
                        <i class="pi pi-user-edit"></i>
                        <div>
                            <span>Cuenta y preferencias</span>
                            <small>Preferencias personales y formatos</small>
                        </div>
                    </button>

                    <button type="button" class="topbar-action-panel-item" @click="openRoute('api-tokens')">
                        <i class="pi pi-key"></i>
                        <div>
                            <span>API Tokens</span>
                            <small>Credenciales para integraciones</small>
                        </div>
                    </button>

                    <button v-if="canManageSettings" type="button" class="topbar-action-panel-item" @click="openRoute('system-settings')">
                        <i class="pi pi-sliders-v"></i>
                        <div>
                            <span>Configuracion del sistema</span>
                            <small>Control global del shell y operacion</small>
                        </div>
                    </button>

                    <button v-if="canManageModules" type="button" class="topbar-action-panel-item" @click="openRoute('system-modules')">
                        <i class="pi pi-box"></i>
                        <div>
                            <span>Administracion de modulos</span>
                            <small>Activacion, dependencias y estado</small>
                        </div>
                    </button>

                    <button type="button" class="topbar-action-panel-item danger" @click="logout">
                        <i class="pi pi-sign-out"></i>
                        <div>
                            <span>Cerrar sesion</span>
                            <small>Salir del sistema actual</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <Dialog v-model:visible="contextModalVisible" modal header="Empresa y contexto laboral" :style="{ width: 'min(42rem, 92vw)' }">
        <div class="context-modal">
            <div class="context-section">
                <div class="context-section-header">
                    <h3>Empresa activa</h3>
                    <p>Selecciona la empresa actual de trabajo. El cambio refresca permisos, modulos y contexto disponible.</p>
                </div>
                <Select v-model="contextState.organizationId" :options="organizations" optionLabel="nombre" optionValue="id" placeholder="Seleccionar empresa" class="w-full" :disabled="tenantStore.state.switchingOrganization" />
                <div class="context-actions">
                    <Button label="Actualizar empresa" icon="pi pi-building" :loading="tenantStore.state.switchingOrganization" @click="applyOrganizationChange" />
                </div>
            </div>

            <Divider />

            <div class="context-section">
                <div class="context-section-header">
                    <h3>Contexto laboral</h3>
                    <p>Selecciona la sucursal, oficina o funcion activa dentro de la empresa actual.</p>
                </div>
                <Select
                    v-model="contextState.workAssignmentId"
                    :options="workAssignments"
                    optionLabel="etiqueta_contexto"
                    optionValue="id"
                    placeholder="Seleccionar contexto laboral"
                    class="w-full"
                    :disabled="tenantStore.state.switchingWorkAssignment || workAssignments.length === 0"
                />
                <small v-if="workAssignments.length === 0" class="text-slate-500">No hay contextos laborales disponibles para la empresa activa.</small>
                <div class="context-actions">
                    <Button label="Aplicar contexto" icon="pi pi-briefcase" :loading="tenantStore.state.switchingWorkAssignment" @click="applyWorkAssignmentChange" />
                </div>
            </div>
        </div>
    </Dialog>
</template>

<style scoped>
.topbar-bell-button {
    position: relative;
}

.topbar-bell-badge {
    position: absolute;
    top: 0.2rem;
    right: 0.2rem;
    min-width: 1.1rem;
    height: 1.1rem;
    border-radius: 999px;
    background: #dc2626;
    color: white;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.25rem;
}

.topbar-account-wrapper {
    position: relative;
}

.topbar-account-trigger {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border: 1px solid var(--surface-border);
    border-radius: 999px;
    background: var(--surface-card);
    color: var(--text-color);
    padding: 0.35rem 0.75rem 0.35rem 0.95rem;
    cursor: pointer;
}

.topbar-user-summary {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    text-align: left;
}

.topbar-user-name {
    font-weight: 600;
    line-height: 1.2;
}

.topbar-user-email {
    font-size: 0.82rem;
    opacity: 0.72;
    line-height: 1.2;
}

.topbar-account-panel {
    position: absolute;
    top: calc(100% + 0.65rem);
    right: 0;
    z-index: 20;
    width: min(22rem, 92vw);
    display: grid;
    gap: 0.55rem;
    border: 1px solid var(--surface-border);
    border-radius: 1.2rem;
    background: var(--surface-card);
    padding: 0.9rem;
    box-shadow: 0 24px 64px rgba(15, 23, 42, 0.16);
}

.topbar-impersonation-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.85rem 1rem;
    border-radius: 1rem;
    background: #fff7ed;
    color: #9a3412;
}

.topbar-impersonation-button {
    border: 0;
    border-radius: 999px;
    background: #ea580c;
    color: white;
    padding: 0.5rem 0.9rem;
    cursor: pointer;
    font-weight: 600;
}

.topbar-action-panel-item {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    width: 100%;
    border: 1px solid var(--surface-border);
    border-radius: 0.95rem;
    background: var(--surface-ground);
    color: var(--text-color);
    padding: 0.85rem 0.95rem;
    cursor: pointer;
    text-align: left;
}

.topbar-action-panel-item i {
    font-size: 1rem;
}

.topbar-action-panel-item div {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.topbar-action-panel-item small {
    opacity: 0.7;
}

.topbar-action-panel-item.danger {
    border-color: #fecaca;
    background: #fef2f2;
    color: #991b1b;
}

.context-modal {
    display: grid;
    gap: 1rem;
}

.context-section {
    display: grid;
    gap: 1rem;
}

.context-section-header {
    display: grid;
    gap: 0.35rem;
}

.context-section-header h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
}

.context-section-header p {
    margin: 0;
    color: #64748b;
}

.context-actions {
    display: flex;
    justify-content: flex-end;
}
</style>
