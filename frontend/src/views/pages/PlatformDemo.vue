<script setup>
const demoCapabilities = [
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
        status: 'Pendiente',
        description: 'Permitira validar tablas, formularios, filtros guardados y serializacion uniforme.'
    },
    {
        title: 'Exportaciones e importaciones',
        status: 'Pendiente',
        description: 'Servira para probar CSV, Excel, PDF y flujos asincronos de gran volumen.'
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
            <p class="m-0 text-color-secondary">
                Este modulo existe para probar de forma aislada las capacidades genericas del StackBase antes de usarlas en modulos de negocio reales.
            </p>
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
                    <router-link v-if="capability.to" :to="capability.to" class="text-primary font-medium no-underline">
                        Abrir demo
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>
