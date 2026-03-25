<template>
    <div class="state-skeleton">
        <div class="state-skeleton-line state-skeleton-line-lg"></div>
        <div class="state-skeleton-line"></div>
        <div class="state-skeleton-grid">
            <Skeleton height="3rem" borderRadius="1rem" />
            <Skeleton height="3rem" borderRadius="1rem" />
            <Skeleton height="3rem" borderRadius="1rem" />
        </div>
        <Skeleton height="16rem" borderRadius="1.25rem" />
    </div>
</template>

<style scoped>
.state-skeleton {
    display: grid;
    gap: 1rem;
}

.state-skeleton-line {
    height: 1rem;
    width: 45%;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--surface-200), var(--surface-100), var(--surface-200));
}

.state-skeleton-line-lg {
    height: 1.35rem;
    width: 30%;
}

.state-skeleton-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
    gap: 1rem;
}
</style>
