<script setup>
defineProps({
    title: {
        type: String,
        default: 'Sin resultados'
    },
    description: {
        type: String,
        default: 'Todavia no hay informacion para mostrar.'
    },
    actionLabel: {
        type: String,
        default: null
    },
    icon: {
        type: String,
        default: 'pi pi-inbox'
    }
});

const emit = defineEmits(['action']);
</script>

<template>
    <div class="state-empty">
        <i :class="['state-empty-icon', icon]"></i>
        <div class="state-empty-title">{{ title }}</div>
        <p class="state-empty-description">{{ description }}</p>
        <Button v-if="actionLabel" :label="actionLabel" severity="secondary" outlined @click="emit('action')" />
    </div>
</template>

<style scoped>
.state-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.85rem;
    border: 1px dashed var(--surface-border);
    border-radius: 1.5rem;
    padding: 2rem 1.5rem;
    background: linear-gradient(180deg, var(--surface-ground), var(--surface-card));
    text-align: center;
}

.state-empty-icon {
    font-size: 1.6rem;
    color: var(--primary-color);
}

.state-empty-title {
    font-weight: 700;
    font-size: 1.1rem;
}

.state-empty-description {
    margin: 0;
    max-width: 38rem;
    color: var(--text-color-secondary);
}
</style>
