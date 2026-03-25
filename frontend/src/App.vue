<script setup>
import { sessionStore } from '@/core/auth/sessionStore';
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import { notificationStore } from '@/core/notifications/notificationStore';
import { onMounted } from 'vue';

onMounted(async () => {
    await sessionStore.initialize();

    if (sessionStore.isAuthenticated.value) {
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
