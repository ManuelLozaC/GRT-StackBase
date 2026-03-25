<script setup>
import { sessionStore } from '@/core/auth/sessionStore';
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import { notificationStore } from '@/core/notifications/notificationStore';
import { settingsStore } from '@/core/settings/settingsStore';
import { onMounted } from 'vue';

onMounted(async () => {
    await sessionStore.initialize();

    if (sessionStore.isAuthenticated.value) {
        await settingsStore.initialize();
        await moduleCatalog.loadModules();
        await notificationStore.loadNotifications();
    }
});
</script>

<template>
    <router-view />
</template>

<style lang="scss">
/* El shell visual hereda algunas clases globales del kit base fuera de Tailwind */
html {
    font-size: 14px;
}
</style>
