<script setup>
import { useAuthStore } from '@/stores/auth';
import { useLayout } from '@/layout/composables/layout';
import { computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const authStore = useAuthStore();
const { toggleMenu, toggleDarkMode, isDarkTheme } = useLayout();

const nombreUsuario = computed(() => authStore.usuario?.nombre_mostrar || 'Usuario');

async function cerrarSesion() {
    await authStore.cerrarSesion();
    router.push({ name: 'login' });
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
            <button type="button" class="layout-topbar-action" @click="toggleDarkMode">
                <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
            </button>

            <div class="layout-topbar-menu lg:block">
                <div class="layout-topbar-menu-content">
                    <button type="button" class="layout-topbar-action">
                        <i class="pi pi-user"></i>
                        <span>{{ nombreUsuario }}</span>
                    </button>
                    <button type="button" class="layout-topbar-action" @click="cerrarSesion">
                        <i class="pi pi-sign-out"></i>
                        <span>Salir</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
