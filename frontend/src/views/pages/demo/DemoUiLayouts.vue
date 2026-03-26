<script setup>
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';
import { computed, ref } from 'vue';

const activeSection = ref('overview');

const breadcrumbItems = [{ label: 'Demo' }, { label: 'UI' }, { label: 'Layouts' }];

const sections = [
    { label: 'Overview', value: 'overview' },
    { label: 'Details', value: 'details' },
    { label: 'History', value: 'history' }
];

const asideCards = [
    { title: 'Empresa activa', value: 'GRT SRL', detail: 'Tenant actual del contexto' },
    { title: 'Oficina', value: 'TalentHub', detail: 'Sucursal o contexto operativo' },
    { title: 'Owner', value: 'Manuel Loza', detail: 'Responsable visible de la entidad' }
];

const activityItems = ['Cabecera con acciones primarias y secundarias', 'Breadcrumb contextual', 'Tabs o secciones visibles', 'Aside de contexto o resumen', 'Bloques principales bien separados'];

const currentSectionSummary = computed(() => {
    return (
        {
            overview: 'Vista principal con KPIs, resumen y CTA.',
            details: 'Vista orientada a atributos, configuracion o lectura profunda.',
            history: 'Vista pensada para trazabilidad y timeline.'
        }[activeSection.value] ?? ''
    );
});
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / UI Layouts" class="w-fit" />
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <h2 class="m-0">Layouts y navegacion de pantalla</h2>
                        <p class="m-0 text-color-secondary">Esta demo muestra una composicion reutilizable para paginas administrativas: breadcrumb, cabecera, tabs, bloque principal, aside de contexto y tarjetas auxiliares.</p>
                    </div>
                    <div class="layout-actions">
                        <Button label="Editar" />
                        <Button label="Exportar" severity="secondary" outlined />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="layout-breadcrumb">
                <template v-for="(item, index) in breadcrumbItems" :key="item.label">
                    <span>{{ item.label }}</span>
                    <i v-if="index < breadcrumbItems.length - 1" class="pi pi-angle-right"></i>
                </template>
            </div>
        </div>

        <div class="col-span-12">
            <div class="layout-tab-row">
                <Button
                    v-for="section in sections"
                    :key="section.value"
                    :label="section.label"
                    :severity="activeSection === section.value ? 'contrast' : 'secondary'"
                    :outlined="activeSection !== section.value"
                    @click="activeSection = section.value"
                />
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="card flex flex-col gap-4">
                <div class="layout-hero">
                    <div>
                        <h3 class="m-0 mb-2">Cabecera de entidad</h3>
                        <p class="m-0 text-color-secondary">{{ currentSectionSummary }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Tag severity="success" value="Activo" />
                        <Chip label="Multi-sucursal" icon="pi pi-building" />
                        <Chip label="Aprobaciones" icon="pi pi-check-square" />
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <div class="layout-panel">
                            <div class="font-semibold mb-2">Bloque principal</div>
                            <p class="m-0 text-sm text-color-secondary">Este panel representa el contenido mas importante de la pantalla: resumen, formulario principal o vista operativa del recurso.</p>
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div class="layout-panel">
                            <div class="font-semibold mb-2">Bloque complementario</div>
                            <p class="m-0 text-sm text-color-secondary">Aqui suele vivir informacion secundaria, configuracion breve o indicadores relacionados con la entidad principal.</p>
                        </div>
                    </div>
                </div>

                <div class="layout-panel">
                    <div class="font-semibold mb-2">Checklist de composicion</div>
                    <ul class="layout-checklist">
                        <li v-for="item in activityItems" :key="item">{{ item }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Aside de contexto</h3>
                    <p class="m-0 text-sm text-color-secondary">Resumen lateral ideal para entidades, procesos o pantallas de detalle.</p>
                </div>

                <div class="layout-aside-list">
                    <article v-for="card in asideCards" :key="card.title" class="layout-aside-card">
                        <div class="text-sm text-color-secondary">{{ card.title }}</div>
                        <div class="font-semibold">{{ card.value }}</div>
                        <div class="text-sm text-color-secondary">{{ card.detail }}</div>
                    </article>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <DemoPatternGuide
                title="Guia para layouts y navegacion"
                :when-to-use="['cuando una pantalla necesita header, contexto y acciones bien separadas', 'cuando el aside realmente reduce saltos de contexto', 'cuando una entidad tiene varias vistas hermanas como overview, details o history']"
                :avoid-when="['cuando la pantalla es demasiado simple y un layout complejo solo agrega ruido', 'cuando se usa aside solo por costumbre sin contenido util', 'cuando tabs visuales ocultan que en realidad se necesitan rutas separadas']"
                :wiring="[
                    'mantener breadcrumb, header y acciones primarias arriba de la jerarquia',
                    'usar bloques principales y laterales con responsabilidades distintas',
                    'si las secciones crecen mucho, convertir tabs visuales en navegacion formal'
                ]"
                :notes="['esta demo sirve como base para pantallas de detalle y paneles administrativos', 'un buen layout reduce la necesidad de explicar demasiado la pantalla']"
            />
        </div>
    </div>
</template>

<style scoped>
.layout-actions,
.layout-tab-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.layout-breadcrumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.65rem;
    color: var(--text-color-secondary);
    font-size: 0.92rem;
}

.layout-hero,
.layout-panel,
.layout-aside-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-ground);
    padding: 1rem;
}

.layout-hero {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 1rem;
}

.layout-checklist {
    margin: 0;
    padding-left: 1.15rem;
    color: var(--text-color-secondary);
    display: grid;
    gap: 0.45rem;
}

.layout-aside-list {
    display: grid;
    gap: 0.85rem;
}
</style>
