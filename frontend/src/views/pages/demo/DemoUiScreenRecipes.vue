<script setup>
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';

const recipes = [
    {
        title: 'Pantalla de listado administrativo',
        sections: ['header con CTA', 'filtros rapidos', 'tabla paginada', 'empty state', 'acciones por fila'],
        description: 'Ideal para usuarios, oficinas, personas, webhooks o cualquier catalogo operacional.'
    },
    {
        title: 'Pantalla de detalle con aside',
        sections: ['breadcrumb', 'header contextual', 'contenido principal', 'aside de resumen', 'timeline o actividad'],
        description: 'Funciona bien para entidades con contexto, aprobaciones o trazabilidad.'
    },
    {
        title: 'Pantalla de formulario largo',
        sections: ['introduccion corta', 'secciones por bloque', 'validacion visible', 'acciones sticky o footer claro', 'resumen lateral si aplica'],
        description: 'Pensada para configuraciones, onboarding o modulos con muchas dependencias.'
    }
];
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / UI Screen Recipes" class="w-fit" />
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <h2 class="m-0">Recetas de pantallas completas</h2>
                        <p class="m-0 text-color-secondary">Esta demo no se enfoca en un componente puntual, sino en composiciones completas de pantalla que un equipo puede usar como punto de partida para modulos reales.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Tag value="List" severity="success" />
                        <Tag value="Detail" severity="info" />
                        <Tag value="Form" severity="warn" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="recipe-stack">
                <article v-for="recipe in recipes" :key="recipe.title" class="card flex flex-col gap-4">
                    <div>
                        <h3 class="m-0 mb-2">{{ recipe.title }}</h3>
                        <p class="m-0 text-sm text-color-secondary">{{ recipe.description }}</p>
                    </div>

                    <div class="recipe-layout">
                        <div class="recipe-header">Header + acciones</div>
                        <div class="recipe-main">
                            <div class="recipe-block">Bloque principal</div>
                            <div class="recipe-block">Bloque secundario</div>
                        </div>
                        <div class="recipe-aside">Aside / contexto</div>
                    </div>

                    <ul class="recipe-list">
                        <li v-for="section in recipe.sections" :key="section">{{ section }}</li>
                    </ul>
                </article>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Reglas de composicion</h3>
                    <p class="m-0 text-sm text-color-secondary">Checklist corto para que las pantallas nuevas no se sientan improvisadas.</p>
                </div>

                <div class="recipe-rule-card">
                    <strong>No mezclar demasiados patrones a la vez</strong>
                    <p class="m-0 text-sm text-color-secondary">Una pantalla debe tener una jerarquia clara, no competir por atencion en todos sus bloques.</p>
                </div>

                <div class="recipe-rule-card">
                    <strong>Header siempre orientado a tarea</strong>
                    <p class="m-0 text-sm text-color-secondary">Titulo, subtitulo y acciones visibles deben responder rapidamente “que es esta pantalla” y “que puedo hacer aqui”.</p>
                </div>

                <div class="recipe-rule-card">
                    <strong>Aside solo cuando aporte contexto real</strong>
                    <p class="m-0 text-sm text-color-secondary">No uses panel lateral por costumbre; usalo cuando reduzca saltos de contexto.</p>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <DemoPatternGuide
                title="Guia para recipes de pantalla"
                :when-to-use="[
                    'cuando el equipo necesita una receta completa y no solo componentes aislados',
                    'cuando se quiere acelerar el armado de nuevos modulos con una composicion ya pensada',
                    'cuando varias pantallas comparten la misma estructura base'
                ]"
                :avoid-when="['cuando se copia una recipe completa sin adaptarla al caso real', 'cuando una pantalla simple termina inflada por seguir una plantilla demasiado grande', 'cuando se mezclan recipes incompatibles en una sola vista']"
                :wiring="['elegir primero la receta correcta: list, detail o form', 'rellenar luego con patrones del resto del Demo Module', 'mantener consistencia entre header, acciones, aside y contenido principal']"
                :notes="['las recipes existen para acelerar decisiones, no para congelar el diseño', 'si una pantalla no cabe en ninguna recipe, probablemente merece una nueva']"
            />
        </div>
    </div>
</template>

<style scoped>
.recipe-stack {
    display: grid;
    gap: 1rem;
}

.recipe-layout {
    display: grid;
    gap: 0.85rem;
    grid-template-columns: 2fr 1fr;
    grid-template-areas:
        'header header'
        'main aside';
}

.recipe-header,
.recipe-main,
.recipe-aside,
.recipe-block,
.recipe-rule-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-ground);
    padding: 1rem;
}

.recipe-header {
    grid-area: header;
}

.recipe-main {
    grid-area: main;
    display: grid;
    gap: 0.75rem;
}

.recipe-aside {
    grid-area: aside;
}

.recipe-list {
    margin: 0;
    padding-left: 1.15rem;
    display: grid;
    gap: 0.5rem;
    color: var(--text-color-secondary);
}

@media (max-width: 768px) {
    .recipe-layout {
        grid-template-columns: 1fr;
        grid-template-areas:
            'header'
            'main'
            'aside';
    }
}
</style>
