<script setup>
const sections = [
    {
        key: 'tutorials',
        title: 'Tutoriales guiados',
        description: 'Entradas recomendadas para onboarding tecnico y para aprender a construir modulos reales sin improvisar arquitectura.',
        severity: 'warning',
        items: [
            {
                title: 'Tutorial base de nuevo modulo',
                status: 'Guiado',
                description: 'Recorrido paso a paso para entender el contrato modular, permisos, metadata, Data Engine y decisiones transversales.',
                to: '/demo/module-tutorial'
            },
            {
                title: 'Tutorial completo de Noticias',
                status: 'Guiado',
                description: 'Caso end-to-end con roles Autor/Editor, aprobacion, notificaciones, export CSV y publicacion controlada.',
                to: '/demo/news-module-tutorial'
            }
        ]
    },
    {
        key: 'core',
        title: 'Capacidades tecnicas del core',
        description: 'Demos funcionales del stack transversal antes de usarlas en modulos de negocio.',
        severity: 'info',
        items: [
            {
                title: 'Gestion de archivos y descargas',
                status: 'Funcional',
                description: 'Subida, descarga directa, signed URLs e historial por usuario dentro del tenant activo.',
                to: '/demo/files'
            },
            {
                title: 'Notificaciones',
                status: 'Funcional',
                description: 'Canales internos, email, push, historial de entregas y estados operativos.',
                to: '/demo/notifications'
            },
            {
                title: 'Jobs y procesos asincronos',
                status: 'Funcional',
                description: 'Dispatch a cola, modo inmediato, retries, logs y diagnostico de worker local.',
                to: '/demo/jobs'
            },
            {
                title: 'Logs y auditoria',
                status: 'Funcional',
                description: 'Trazabilidad funcional y tecnica para modulos, archivos, jobs y cambios administrativos.',
                to: '/demo/audit'
            },
            {
                title: 'Exportaciones e importaciones',
                status: 'Funcional',
                description: 'CSV, Excel, PDF, historial de corridas y flujos async sobre el recurso demo.',
                to: '/demo/transfers'
            },
            {
                title: 'Data Engine',
                status: 'Funcional',
                description: 'CRUD universal tenant-aware con filtros, relaciones, custom fields, import/export y busqueda real.',
                to: '/platform/data-engine'
            }
        ]
    },
    {
        key: 'ui',
        title: 'Patrones UI y recipes',
        description: 'Biblioteca visible de patrones del shell para evitar estilos ad hoc y wiring inconsistente.',
        severity: 'success',
        items: [
            {
                title: 'UI Showcase',
                status: 'Funcional',
                description: 'Panorama general de componentes, patrones y densidad visual del sistema.',
                to: '/demo/ui-showcase'
            },
            {
                title: 'UI Feedback',
                status: 'Funcional',
                description: 'Toasts, confirmaciones, overlays, banners, empty states y mensajes embebidos.',
                to: '/demo/ui-feedback'
            },
            {
                title: 'UI Forms e inputs',
                status: 'Funcional',
                description: 'Formularios base, validaciones, inputs ricos y reglas de composicion.',
                to: '/demo/ui-forms'
            },
            {
                title: 'UI Data Display',
                status: 'Funcional',
                description: 'Cards, tablas, tags y listados compactos para superficies administrativas.',
                to: '/demo/ui-data-display'
            },
            {
                title: 'UI Async Patterns',
                status: 'Funcional',
                description: 'Submit con loading, retry visible, polling y progreso para procesos largos.',
                to: '/demo/ui-async-patterns'
            },
            {
                title: 'UI Layouts',
                status: 'Funcional',
                description: 'Headers, bloques, tabs, asides y composiciones de pantalla reutilizables.',
                to: '/demo/ui-layouts'
            },
            {
                title: 'UI Typography y contenido',
                status: 'Funcional',
                description: 'Jerarquia de titulos, parrafos, ayudas editoriales y bloques informativos.',
                to: '/demo/ui-typography-content'
            },
            {
                title: 'UI Advanced Inputs',
                status: 'Funcional',
                description: 'Patrones con SelectButton, rangos de fecha y combinaciones de input menos triviales.',
                to: '/demo/ui-advanced-inputs'
            },
            {
                title: 'UI Screen Recipes',
                status: 'Funcional',
                description: 'Recetas de pantalla completas para listados, formularios largos y detalle con aside.',
                to: '/demo/ui-screen-recipes'
            }
        ]
    }
];
</script>

<template>
    <div class="space-y-6">
        <div class="card">
            <div class="flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module" class="w-fit" />
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="max-w-4xl">
                        <h2 class="m-0">Platform Demo</h2>
                        <p class="m-0 text-color-secondary">
                            Este modulo existe para probar capacidades genericas del StackBase, aprender patrones de implementacion y evitar que cada modulo nuevo reinvente UX, wiring o decisiones tecnicas ya resueltas.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Tag severity="contrast" value="Biblioteca viva" />
                        <Tag severity="info" value="Onboarding tecnico" />
                        <Tag severity="success" value="Patrones reutilizables" />
                    </div>
                </div>
            </div>
        </div>

        <section v-for="section in sections" :key="section.key" class="card">
            <div class="app-panel-header mb-5">
                <div class="app-panel-header-copy">
                    <div class="flex items-center gap-3 mb-2">
                        <Tag :severity="section.severity" :value="section.title" />
                    </div>
                    <p class="m-0 text-color-secondary">{{ section.description }}</p>
                </div>
                <div class="app-panel-actions">
                    <Tag severity="contrast" :value="`${section.items.length} demo${section.items.length === 1 ? '' : 's'}`" />
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div v-for="capability in section.items" :key="capability.title" class="col-span-12 md:col-span-6 xl:col-span-4">
                    <div class="border rounded-xl p-4 h-full surface-border">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="font-semibold">{{ capability.title }}</div>
                            <Tag :severity="capability.status === 'Guiado' ? 'warning' : 'success'" :value="capability.status" />
                        </div>
                        <p class="m-0 text-sm text-color-secondary mb-4">
                            {{ capability.description }}
                        </p>
                        <router-link v-if="capability.to" :to="capability.to" class="text-primary font-medium no-underline">Abrir demo</router-link>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
