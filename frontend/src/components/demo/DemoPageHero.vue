<script setup>
defineProps({
    badge: {
        type: String,
        required: true
    },
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        required: true
    }
});
</script>

<template>
    <div class="card flex flex-col gap-3">
        <Tag severity="warning" :value="badge" class="w-fit" />
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <h2 class="m-0">{{ title }}</h2>
                <p class="m-0 text-color-secondary">{{ description }}</p>
            </div>
            <div class="demo-hero-aside">
                <slot name="aside" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.demo-hero-aside {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}
</style>
