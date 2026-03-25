<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">StackBase</div>
            <h1 class="text-3xl font-semibold text-slate-900 mb-3">Documentacion operativa</h1>
            <p class="text-slate-600 max-w-3xl">Esta vista reemplaza la documentacion del template original. Su objetivo es resumir la arquitectura activa del proyecto y orientar el siguiente desarrollo sobre `Core Platform + Modules`.</p>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Core</div>
                    <ul class="space-y-3 text-slate-700 list-disc pl-5">
                        <li>Autenticacion API consumida desde stores separados de sesion, tenant y permisos</li>
                        <li>Tenancy base reforzada con `TenantContext` en request, jobs, notificaciones y archivos base</li>
                        <li>Modulos instalables consumidos por API como fuente de verdad y con dependencias operativas</li>
                        <li>Data Engine universal con CRUD demo reutilizable por metadata, relaciones, custom fields y transferencias `CSV / Excel / PDF` auditables</li>
                        <li>Settings globales, por organizacion y por usuario con feature flags base</li>
                        <li>Servicios de archivos, jobs, auditoria y notificaciones internas/multicanal base</li>
                        <li>Request IDs, rate limiting base y vistas operativas de seguridad/operations para soporte</li>
                        <li>Error logs, metricas internas y formato locale-aware ya forman parte del core operativo</li>
                        <li>API tokens, sanitizacion base y performance metrics ya existen como base de integracion y operacion</li>
                        <li>Respuesta estandar en `api/v1`</li>
                    </ul>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Demo Module</div>
                    <ul class="space-y-3 text-slate-700 list-disc pl-5">
                        <li>Rutas y menu bootstrappeados desde metadata modular backend</li>
                        <li>Demo funcional de archivos</li>
                        <li>Demo funcional de jobs</li>
                        <li>Demo funcional de auditoria</li>
                        <li>Demo funcional de notificaciones</li>
                        <li>Recurso demo activo para validar CRUD universal, export/import, relaciones, custom fields y corridas async</li>
                        <li>La demo de notificaciones ya expone canales, entregas y estados operativos</li>
                        <li>Shell con banner global y feedback HTTP transversal</li>
                        <li>El shell ya incluye `Security Logs` y `Operations Overview` para visibilidad del tenant activo</li>
                        <li>La shell administrativa ya suma `Error Logs` y `Usage Metrics` sin depender de wiring legacy</li>
                        <li>El Data Engine ya permite persistir columnas visibles por usuario y la cuenta ya gestiona `API Tokens`</li>
                    </ul>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Siguiente foco</div>
                    <ul class="space-y-3 text-slate-700 list-disc pl-5">
                        <li>Generalizar el contrato modular para futuros modulos</li>
                        <li>Propagacion tenant consistente en todo el dominio</li>
                        <li>Storage real con Spaces</li>
                        <li>Operaciones pesadas async, integraciones externas y observabilidad operativa</li>
                    </ul>
                </div>
            </div>

            <div class="col-span-12">
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-slate-700">
                    La fuente maestra sigue estando en `docs/roadmap.md`, `docs/pendientes.md` y `docs/stackbase.md`. Esta pantalla existe para que el frontend no exponga mas contenido heredado del template original.
                </div>
            </div>
        </div>
    </div>
</template>
