<script setup>
import DemoPageHero from '@/components/demo/DemoPageHero.vue';
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';
import StateEmpty from '@/components/core/StateEmpty.vue';
import { computed, ref } from 'vue';

const globalFilter = ref('');

const cards = [
    {
        title: 'Clientes activos',
        value: 18,
        detail: 'Empresas con usuarios y oficinas operativas.',
        severity: 'success'
    },
    {
        title: 'Procesos pendientes',
        value: 6,
        detail: 'Flujos con validacion o aprobacion aun abiertos.',
        severity: 'warn'
    },
    {
        title: 'Integraciones',
        value: 4,
        detail: 'Conectores o webhooks ya habilitados.',
        severity: 'info'
    }
];

const rows = [
    { id: 1, modulo: 'Usuarios', patron: 'Tabla con acciones', estado: 'Listo', owner: 'Core' },
    { id: 2, modulo: 'Oficinas', patron: 'Filtro y paginacion', estado: 'Listo', owner: 'Data Engine' },
    { id: 3, modulo: 'Files', patron: 'Card con metadatos', estado: 'Listo', owner: 'Core Files' },
    { id: 4, modulo: 'Demo UI', patron: 'Toolbar y badges', estado: 'En diseno', owner: 'Demo Module' },
    { id: 5, modulo: 'Jobs', patron: 'Timeline basica', estado: 'Pendiente', owner: 'Core Jobs' },
    { id: 6, modulo: 'Settings', patron: 'Detalle lateral', estado: 'Listo', owner: 'Core Settings' }
];

const filteredRows = computed(() => {
    const term = globalFilter.value.trim().toLowerCase();

    if (!term) {
        return rows;
    }

    return rows.filter((row) => Object.values(row).some((value) => String(value).toLowerCase().includes(term)));
});

function resolveSeverity(status) {
    return (
        {
            Listo: 'success',
            'En diseno': 'warn',
            Pendiente: 'secondary'
        }[status] ?? 'contrast'
    );
}
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <DemoPageHero
                badge="Demo Module / UI Data Display"
                title="Tablas, cards y listados"
                description="Esta demo muestra patrones de presentacion de datos para dashboards administrativos, tablas filtrables, cards resumen y listados con acciones visibles."
            >
                <template #aside>
                    <div class="toolbar-actions">
                        <Button label="Accion primaria" />
                        <Button label="Exportar" severity="secondary" outlined />
                    </div>
                </template>
            </DemoPageHero>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-12 gap-4">
                <div v-for="card in cards" :key="card.title" class="col-span-12 md:col-span-4">
                    <div class="metric-card">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="metric-title">{{ card.title }}</div>
                                <p class="metric-detail">{{ card.detail }}</p>
                            </div>
                            <Tag :severity="card.severity" :value="card.value" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="card flex flex-col gap-4 h-full">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="m-0 mb-2">Tabla de referencia</h3>
                        <p class="m-0 text-sm text-color-secondary">Patron base para tablas con filtro, tags de estado, paginacion y acciones.</p>
                    </div>
                    <InputText v-model="globalFilter" placeholder="Buscar por modulo, patron o owner" class="w-full lg:w-20rem" />
                </div>

                <DataTable :value="filteredRows" paginator :rows="4" responsiveLayout="scroll" size="small">
                    <Column field="modulo" header="Modulo" />
                    <Column field="patron" header="Patron" />
                    <Column field="owner" header="Owner" />
                    <Column field="estado" header="Estado">
                        <template #body="{ data }">
                            <Tag :severity="resolveSeverity(data.estado)" :value="data.estado" />
                        </template>
                    </Column>
                    <Column header="Acciones">
                        <template #body>
                            <div class="table-actions">
                                <Button icon="pi pi-eye" rounded text />
                                <Button icon="pi pi-pencil" rounded text />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Listado compacto</h3>
                    <p class="m-0 text-sm text-color-secondary">Ejemplo util para side panels, dashboards o vistas resumidas.</p>
                </div>

                <div v-if="filteredRows.length" class="compact-list">
                    <article v-for="row in filteredRows.slice(0, 4)" :key="row.id" class="compact-card">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ row.modulo }}</div>
                                <div class="text-sm text-color-secondary">{{ row.patron }}</div>
                            </div>
                            <Tag :severity="resolveSeverity(row.estado)" :value="row.estado" />
                        </div>
                        <div class="text-sm text-color-secondary">Owner: {{ row.owner }}</div>
                    </article>
                </div>

                <StateEmpty v-else title="Sin coincidencias" description="Este patron funciona bien para filtros que vacian una tabla o listado lateral." icon="pi pi-search" />
            </div>
        </div>

        <div class="col-span-12">
            <DemoPatternGuide
                title="Guia para data display"
                :when-to-use="['cuando necesitas combinar resumen visual con tabla operativa', 'cuando la pantalla debe mostrar estado, owner y acciones en un mismo lugar', 'cuando conviene ofrecer una vista compacta ademas de la tabla principal']"
                :avoid-when="['cuando una tabla ya resuelve todo y las cards no agregan contexto', 'cuando el dashboard intenta mostrar demasiados KPIs irrelevantes', 'cuando los filtros son tan complejos que merecen una pantalla propia']"
                :wiring="['usar cards para resumen y tablas para accion', 'mantener tags de estado consistentes en todas las vistas', 'si existe filtro, mostrar tambien un empty state claro cuando no haya coincidencias']"
                :notes="['este patron funciona muy bien para modulos administrativos y pantallas de supervision', 'si la lectura manda sobre la accion, priorizar cards o listados antes que una tabla pesada']"
            />
        </div>
    </div>
</template>

<style scoped>
.toolbar-actions,
.table-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.metric-card {
    border: 1px solid var(--surface-border);
    border-radius: 1.25rem;
    background: linear-gradient(135deg, var(--surface-card), var(--surface-ground));
    padding: 1.1rem;
}

.metric-title {
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.metric-detail {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text-color-secondary);
}

.compact-list {
    display: grid;
    gap: 0.85rem;
}

.compact-card {
    display: grid;
    gap: 0.65rem;
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-ground);
    padding: 1rem;
}
</style>
