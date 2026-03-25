<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">StackBase</div>
            <h1 class="text-3xl font-semibold text-slate-900 mb-3">Documentacion operativa</h1>
            <p class="text-slate-600 max-w-3xl">
                Esta vista reemplaza la documentacion del template original. Su objetivo es resumir la arquitectura activa del proyecto y orientar el siguiente desarrollo sobre `Core Platform + Modules`.
            </p>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Core</div>
                    <ul class="space-y-3 text-slate-700 list-disc pl-5">
                        <li>Autenticacion API y tenancy base</li>
                        <li>Modulos instalables con activacion por administracion</li>
                        <li>Servicios de archivos, jobs, auditoria y notificaciones</li>
                        <li>Respuesta estandar en `api/v1`</li>
                    </ul>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Demo Module</div>
                    <ul class="space-y-3 text-slate-700 list-disc pl-5">
                        <li>Demo funcional de archivos</li>
                        <li>Demo funcional de jobs</li>
                        <li>Demo funcional de auditoria</li>
                        <li>Demo funcional de notificaciones</li>
                    </ul>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Siguiente foco</div>
                    <ul class="space-y-3 text-slate-700 list-disc pl-5">
                        <li>Motor CRUD universal</li>
                        <li>Propagacion tenant consistente</li>
                        <li>Storage real con Spaces</li>
                        <li>Export/import demo funcional</li>
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
