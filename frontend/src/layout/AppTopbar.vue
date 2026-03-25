<script setup>
import { accessStore } from '@/core/auth/accessStore';
import { sessionStore } from '@/core/auth/sessionStore';
import { settingsStore } from '@/core/settings/settingsStore';
import { tenantStore } from '@/core/auth/tenantStore';
import { notificationStore } from '@/core/notifications/notificationStore';
import { useLayout } from '@/layout/composables/layout';
import { useToast } from 'primevue/usetoast';
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import AppConfigurator from './AppConfigurator.vue';

const router = useRouter();
const toast = useToast();
const { toggleMenu, toggleDarkMode, isDarkTheme } = useLayout();
const organizations = computed(() => tenantStore.organizations.value);
const activeOrganizationId = computed(() => tenantStore.activeOrganization.value?.id ?? '');
const unreadNotifications = computed(() => notificationStore.unreadCount.value);
const canManageSettings = computed(() => accessStore.hasPermission('settings.manage'));

async function logout() {
    await sessionStore.logout();

    toast.add({
        severity: 'success',
        summary: 'Sesion cerrada',
        detail: 'Hasta pronto.',
        life: 2500
    });

    await router.push({
        name: 'login'
    });
}

async function switchOrganization(event) {
    const selectedId = Number(event.target.value);

    if (!selectedId || selectedId === activeOrganizationId.value) {
        return;
    }

    await tenantStore.switchActiveOrganization(selectedId);

    toast.add({
        severity: 'success',
        summary: 'Organizacion actualizada',
        detail: 'El contexto activo de trabajo ya cambio.',
        life: 2500
    });
}

async function openNotifications() {
    await notificationStore.loadNotifications();
    await router.push({
        name: 'demo-notifications'
    });
}

async function openPreferences() {
    await router.push({
        name: 'my-preferences'
    });
}

async function openSystemSettings() {
    await router.push({
        name: 'system-settings'
    });
}

async function toggleThemePreference() {
    toggleDarkMode();

    if (!sessionStore.isAuthenticated.value) {
        return;
    }

    const nextTheme = isDarkTheme.value ? 'dark' : 'light';

    try {
        await settingsStore.updateUser({
            theme: nextTheme
        });
    } catch {
        // El toggle visual ya se aplico; si la persistencia falla no bloqueamos la UX local.
    }
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
            <div class="layout-config-menu">
                <button type="button" class="layout-topbar-action topbar-bell-button" @click="openNotifications">
                    <i class="pi pi-bell"></i>
                    <span v-if="unreadNotifications > 0" class="topbar-bell-badge">{{ unreadNotifications }}</span>
                </button>
                <button type="button" class="layout-topbar-action" @click="toggleThemePreference">
                    <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
                </button>
                <div class="relative">
                    <button
                        v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'p-anchored-overlay-enter-active', leaveToClass: 'hidden', leaveActiveClass: 'p-anchored-overlay-leave-active', hideOnOutsideClick: true }"
                        type="button"
                        class="layout-topbar-action layout-topbar-action-highlight"
                    >
                        <i class="pi pi-palette"></i>
                    </button>
                    <AppConfigurator />
                </div>
            </div>

            <button
                class="layout-topbar-menu-button layout-topbar-action"
                v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'p-anchored-overlay-enter-active', leaveToClass: 'hidden', leaveActiveClass: 'p-anchored-overlay-leave-active', hideOnOutsideClick: true }"
            >
                <i class="pi pi-ellipsis-v"></i>
            </button>

            <div class="layout-topbar-menu lg:block">
                <div class="layout-topbar-menu-content">
                    <label v-if="organizations.length > 0" class="topbar-organization-switcher">
                        <span>Organizacion</span>
                        <select :value="activeOrganizationId" :disabled="tenantStore.state.switchingOrganization" @change="switchOrganization">
                            <option v-for="organization in organizations" :key="organization.id" :value="organization.id">
                                {{ organization.nombre }}
                            </option>
                        </select>
                    </label>
                    <button type="button" class="layout-topbar-action">
                        <i class="pi pi-user"></i>
                        <span>{{ sessionStore.state.user?.name || 'Profile' }}</span>
                    </button>
                    <button type="button" class="layout-topbar-action" @click="openPreferences">
                        <i class="pi pi-sliders-h"></i>
                        <span>Preferencias</span>
                    </button>
                    <button v-if="canManageSettings" type="button" class="layout-topbar-action" @click="openSystemSettings">
                        <i class="pi pi-cog"></i>
                        <span>System Settings</span>
                    </button>
                    <button type="button" class="layout-topbar-action">
                        <i class="pi pi-envelope"></i>
                        <span>{{ sessionStore.state.user?.email || 'No email' }}</span>
                    </button>
                    <button type="button" class="layout-topbar-action" @click="logout">
                        <i class="pi pi-sign-out"></i>
                        <span>Logout</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.topbar-organization-switcher {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-color);
}

.topbar-organization-switcher span {
    font-size: 0.875rem;
    white-space: nowrap;
}

.topbar-organization-switcher select {
    min-width: 12rem;
    border: 1px solid var(--surface-border);
    border-radius: 0.5rem;
    background: var(--surface-card);
    color: var(--text-color);
    padding: 0.45rem 0.75rem;
}

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
</style>
