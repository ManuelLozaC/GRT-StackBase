<script setup>
defineProps({
    title: {
        type: String,
        default: 'Guia del patron'
    },
    whenToUse: {
        type: Array,
        default: () => []
    },
    avoidWhen: {
        type: Array,
        default: () => []
    },
    wiring: {
        type: Array,
        default: () => []
    },
    notes: {
        type: Array,
        default: () => []
    }
});
</script>

<template>
    <div class="demo-guide">
        <div class="demo-guide-header">
            <i class="pi pi-compass"></i>
            <strong>{{ title }}</strong>
        </div>

        <div class="demo-guide-grid">
            <section v-if="whenToUse.length" class="demo-guide-card">
                <h4 class="m-0 mb-2">Cuando usar</h4>
                <ul class="demo-guide-list">
                    <li v-for="item in whenToUse" :key="item">{{ item }}</li>
                </ul>
            </section>

            <section v-if="avoidWhen.length" class="demo-guide-card">
                <h4 class="m-0 mb-2">Cuando evitar</h4>
                <ul class="demo-guide-list">
                    <li v-for="item in avoidWhen" :key="item">{{ item }}</li>
                </ul>
            </section>

            <section v-if="wiring.length" class="demo-guide-card">
                <h4 class="m-0 mb-2">Wiring recomendado</h4>
                <ul class="demo-guide-list">
                    <li v-for="item in wiring" :key="item">{{ item }}</li>
                </ul>
            </section>

            <section v-if="notes.length" class="demo-guide-card">
                <h4 class="m-0 mb-2">Notas de implementacion</h4>
                <ul class="demo-guide-list">
                    <li v-for="item in notes" :key="item">{{ item }}</li>
                </ul>
            </section>
        </div>
    </div>
</template>

<style scoped>
.demo-guide {
    border: 1px solid var(--surface-border);
    border-radius: 1.2rem;
    background: linear-gradient(135deg, var(--surface-card), var(--surface-ground));
    padding: 1rem;
}

.demo-guide-header {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 1rem;
}

.demo-guide-grid {
    display: grid;
    gap: 0.85rem;
    grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
}

.demo-guide-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-ground);
    padding: 0.95rem;
}

.demo-guide-list {
    margin: 0;
    padding-left: 1.1rem;
    display: grid;
    gap: 0.45rem;
    color: var(--text-color-secondary);
}
</style>
