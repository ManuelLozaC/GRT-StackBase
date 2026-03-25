<script setup>
import { settingsStore } from '@/core/settings/settingsStore';
import { uiFeedbackStore } from '@/core/ui/uiFeedbackStore';
import { useLayout } from '@/layout/composables/layout';
import { computed } from 'vue';
import AppFooter from './AppFooter.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';

const { layoutConfig, layoutState, hideMobileMenu } = useLayout();

const containerClass = computed(() => {
    return {
        'layout-overlay': layoutConfig.menuMode === 'overlay',
        'layout-static': layoutConfig.menuMode === 'static',
        'layout-overlay-active': layoutState.overlayMenuActive,
        'layout-mobile-active': layoutState.mobileMenuActive,
        'layout-static-inactive': layoutState.staticMenuInactive
    };
});
const globalBanner = computed(() => settingsStore.globalBanner.value);
const httpError = computed(() => uiFeedbackStore.httpError.value);
const showGlobalErrors = computed(() => settingsStore.featureFlags.value.feature_global_error_toasts !== false);
</script>

<template>
    <div class="layout-wrapper" :class="containerClass">
        <AppTopbar />
        <AppSidebar />
        <div class="layout-main-container">
            <div class="layout-main">
                <div v-if="globalBanner.enabled && globalBanner.message" :class="['app-banner', `app-banner-${globalBanner.severity}`]">
                    <i class="pi pi-megaphone"></i>
                    <span>{{ globalBanner.message }}</span>
                </div>
                <div v-if="showGlobalErrors && httpError" class="app-banner app-banner-danger">
                    <i class="pi pi-exclamation-triangle"></i>
                    <span>{{ httpError.message }}</span>
                    <button type="button" class="app-banner-close" @click="uiFeedbackStore.clearHttpError()">Cerrar</button>
                </div>
                <router-view />
            </div>
            <AppFooter />
        </div>
        <div class="layout-mask animate-fadein" @click="hideMobileMenu" />
    </div>
    <Toast />
</template>

<style scoped>
.app-banner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-radius: 1rem;
    padding: 0.85rem 1rem;
    margin-bottom: 1rem;
    font-weight: 500;
}

.app-banner-info {
    background: #e0f2fe;
    color: #0c4a6e;
}

.app-banner-success {
    background: #dcfce7;
    color: #166534;
}

.app-banner-warn {
    background: #fef3c7;
    color: #92400e;
}

.app-banner-danger {
    background: #fee2e2;
    color: #991b1b;
}

.app-banner-close {
    margin-left: auto;
    background: transparent;
    border: 0;
    color: inherit;
    cursor: pointer;
    font-weight: 700;
}
</style>
