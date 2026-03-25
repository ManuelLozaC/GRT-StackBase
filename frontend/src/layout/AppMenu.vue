<script setup>
import { authStore } from '@/core/auth/authStore';
import { coreMenu } from '@/core/navigation/core-menu';
import { moduleCatalog } from '@/core/modules/moduleCatalog';
import { moduleMenu } from '@/modules';
import { computed } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

function filterMenu(items) {
    return items
        .filter((item) => !item.moduleKey || moduleCatalog.isModuleEnabled(item.moduleKey))
        .filter((item) => !item.permissionKey || authStore.hasPermission(item.permissionKey))
        .map((item) => ({
            ...item,
            items: item.items ? filterMenu(item.items) : undefined
        }))
        .filter((item) => !item.items || item.items.length > 0);
}

const model = computed(() => filterMenu([...coreMenu, ...moduleMenu]));
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="`${item.label}-${i}`">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-else class="menu-separator"></li>
        </template>
    </ul>
</template>
