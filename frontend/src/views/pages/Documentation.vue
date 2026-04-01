<script setup>
import { useActionFeedback } from '@/core/ui/useActionFeedback';
import api from '@/service/api';

const feedback = useActionFeedback();

const coreCatalogs = ['Empresas', 'Oficinas', 'Equipos', 'Personas', 'Divisiones', 'Areas', 'Cargos', 'Asignaciones laborales'];

async function openOpenApiJson() {
    try {
        const response = await api.get('/v1/openapi.json', {
            responseType: 'blob'
        });
        const blobUrl = window.URL.createObjectURL(new Blob([response.data], { type: 'application/json' }));
        window.open(blobUrl, '_blank', 'noopener,noreferrer');
    } catch (error) {
        feedback.showError('No se pudo abrir OpenAPI', error, 'Tu usuario no tiene acceso a la documentacion tecnica o el archivo no pudo cargarse.');
    }
}
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-3 text-sm font-semibold uppercase tracking-[0.3em] text-sky-600">StackBase</div>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="mb-3 text-3xl font-semibold text-slate-900">Documentacion operativa</h1>
                    <p class="max-w-3xl text-slate-600">Esta vista resume la arquitectura activa del proyecto, deja visibles los catalogos universales del core y recuerda que el shell base no debe absorber negocio que pertenece a modulos.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition hover:bg-sky-100" @click="openOpenApiJson">
                    <i class="pi pi-external-link"></i>
                    Ver OpenAPI JSON
                </button>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Core</div>
                    <ul class="space-y-3 text-slate-700 list-disc pl-5">
                        <li>Autenticacion API consumida desde stores separados de sesion, tenant y permisos.</li>
                        <li>Tenancy base reforzada con `TenantContext` en request, jobs, notificaciones y archivos.</li>
                        <li>Metadata modular consumida por API como fuente de verdad.</li>
                        <li>Data Engine universal con CRUD, relaciones, custom fields y transferencias auditables.</li>
                        <li>Settings globales, por empresa y por usuario con feature flags.</li>
                        <li>Servicios de archivos, jobs, auditoria, notificaciones, seguridad y observabilidad.</li>
                        <li>API tokens, webhooks, OpenAPI JSON y request IDs como base de integracion y soporte.</li>
                    </ul>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Demo Module</div>
                    <ul class="space-y-3 text-slate-700 list-disc pl-5">
                        <li>Demos tecnicas reales de archivos, jobs, auditoria, notificaciones y transfers.</li>
                        <li>Recipes y patrones UI para formularios, feedback, layouts, inputs y pantallas completas.</li>
                        <li>Tutorial guiado de nuevo modulo y tutorial completo del modulo `Noticias`.</li>
                        <li>Base didactica para onboarding tecnico antes de tocar modulos reales.</li>
                    </ul>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Regla del shell</div>
                    <ul class="space-y-3 text-slate-700 list-disc pl-5">
                        <li>El core solo aloja superficies tecnicas y administrativas realmente transversales.</li>
                        <li>Si una pantalla o flujo solo sirve a un dominio, debe vivir en su modulo.</li>
                        <li>Antes de crear tablas o vistas nuevas, revisa si el catalogo ya existe en el core.</li>
                        <li>Antes de inventar UX nueva, revisa si ya existe una recipe curada en `Demo Module`.</li>
                    </ul>
                </div>
            </div>

            <div class="col-span-12">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="app-panel-header">
                        <div class="app-panel-header-copy">
                            <h2 class="text-xl font-semibold text-slate-900 m-0">Catalogos universales del core</h2>
                            <p class="text-sm text-slate-600 m-0">Estos catalogos ya quedaron cerrados como base comun del sistema y deben reutilizarse desde futuros modulos en lugar de redefinirse.</p>
                        </div>
                        <div class="app-panel-actions">
                            <Tag severity="contrast" :value="`${coreCatalogs.length} catalogos`" />
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <Tag v-for="catalog in coreCatalogs" :key="catalog" severity="info" :value="catalog" />
                    </div>
                </div>
            </div>

            <div class="col-span-12">
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-slate-700">
                    La fuente maestra sigue estando en `docs/roadmap.md`, `docs/pendientes.md`, `docs/stackbase.md` y `docs/modelo_dominio.md`. Esta pantalla existe para dejar visible la arquitectura activa sin arrastrar contenido heredado del
                    template original.
                </div>
            </div>
        </div>
    </div>
</template>
