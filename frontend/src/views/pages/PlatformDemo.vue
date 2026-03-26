<script setup>
const demoCapabilities = [
    {
        title: 'UI Showcase y patrones de interfaz',
        status: 'Funcional',
        description: 'Incluye ejemplos reutilizables de toasts, modals, banners, tipografia, forms, inputs, datepickers, tablas y estados visuales.',
        to: '/demo/ui-showcase'
    },
    {
        title: 'UI Feedback',
        status: 'Funcional',
        description: 'Ejemplos enfocados en toasts, confirmaciones, modals, drawers, loaders, banners y empty states.',
        to: '/demo/ui-feedback'
    },
    {
        title: 'UI Forms e inputs',
        status: 'Funcional',
        description: 'Incluye formularios de referencia con validacion, inputs, password, radios, checkboxes, toggles y file input.',
        to: '/demo/ui-forms'
    },
    {
        title: 'UI Data Display',
        status: 'Funcional',
        description: 'Muestra cards resumen, tablas con filtro, tags de estado, listados compactos y acciones visibles.',
        to: '/demo/ui-data-display'
    },
    {
        title: 'UI Async Patterns',
        status: 'Funcional',
        description: 'Incluye submit con loading, retry visible, polling y progreso para procesos largos o integraciones.',
        to: '/demo/ui-async-patterns'
    },
    {
        title: 'UI Layouts',
        status: 'Funcional',
        description: 'Muestra composiciones de pantalla con breadcrumb, header, tabs visuales, aside y bloques reutilizables.',
        to: '/demo/ui-layouts'
    },
    {
        title: 'UI Typography y contenido',
        status: 'Funcional',
        description: 'Define jerarquias de titulos, parrafos, notas editoriales, listas y bloques de ayuda para pantallas administrativas.',
        to: '/demo/ui-typography-content'
    },
    {
        title: 'UI Advanced Inputs',
        status: 'Funcional',
        description: 'Agrupa inputs menos triviales como SelectButton, rangos de fecha, password con feedback y combinaciones mas ricas.',
        to: '/demo/ui-advanced-inputs'
    },
    {
        title: 'UI Screen Recipes',
        status: 'Funcional',
        description: 'Muestra recetas de pantalla completas para listados, detalle con aside y formularios largos.',
        to: '/demo/ui-screen-recipes'
    },
    {
        title: 'Gestion de archivos y descargas',
        status: 'Funcional',
        description: 'Subida, descarga directa, signed URLs e historial por usuario dentro del tenant activo.',
        to: '/demo/files'
    },
    {
        title: 'Notificaciones',
        status: 'Funcional',
        description: 'Incluye notificaciones internas, bandeja, marcado de lectura y campanita con contador.',
        to: '/demo/notifications'
    },
    {
        title: 'CRUD generico y filtros',
        status: 'Funcional',
        description: 'Valida el Data Engine tenant-aware con tabla, formulario, filtros, busqueda, ordenamiento y soft delete.',
        to: '/platform/data-engine'
    },
    {
        title: 'Exportaciones e importaciones',
        status: 'Funcional',
        description: 'Permite probar CSV, Excel, PDF, historial de corridas e inicio de exportaciones asincronas sobre el recurso demo.',
        to: '/demo/transfers'
    },
    {
        title: 'Jobs y procesos asincronos',
        status: 'Funcional',
        description: 'Incluye dispatch a cola, modo inmediato para pruebas locales, estados, intentos y logs basicos.',
        to: '/demo/jobs'
    },
    {
        title: 'Logs y auditoria',
        status: 'Funcional',
        description: 'Muestra actividad transversal con actor, entidad, fecha y contexto para modulos, archivos y jobs.',
        to: '/demo/audit'
    }
];
</script>

<template>
    <div class="card">
        <div class="flex flex-col gap-3 mb-6">
            <Tag severity="warning" value="Demo Module" class="w-fit" />
            <h2 class="m-0">Platform Demo</h2>
            <p class="m-0 text-color-secondary">Este modulo existe para probar de forma aislada las capacidades genericas del StackBase antes de usarlas en modulos de negocio reales.</p>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div v-for="capability in demoCapabilities" :key="capability.title" class="col-span-12 md:col-span-6 xl:col-span-4">
                <div class="border rounded-xl p-4 h-full surface-border">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="font-semibold">{{ capability.title }}</div>
                        <Tag :severity="capability.status === 'Funcional' ? 'success' : 'secondary'" :value="capability.status" />
                    </div>
                    <p class="m-0 text-sm text-color-secondary mb-4">
                        {{ capability.description }}
                    </p>
                    <router-link v-if="capability.to" :to="capability.to" class="text-primary font-medium no-underline"> Abrir demo </router-link>
                </div>
            </div>
        </div>
    </div>
</template>
