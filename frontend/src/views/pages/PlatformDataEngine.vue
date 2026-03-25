<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Core Platform</div>
            <h1 class="text-3xl font-semibold text-slate-900 mb-3">Data Engine</h1>
            <p class="text-slate-600 max-w-3xl">
                Esta seccion reemplaza el CRUD heredado del template. El proyecto ya no expone un mantenimiento legacy desconectado de `api/v1`: el siguiente desarrollo aqui sera el motor de datos real del core, con CRUD universal, filtros,
                paginacion y contratos reutilizables para modulos.
            </p>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Ya consolidado</div>
                    <ul class="space-y-3 text-slate-700 list-disc pl-5">
                        <li>Autenticacion API y tenancy base</li>
                        <li>Modulos administrables con `Demo Module`</li>
                        <li>Servicios transversales: archivos, jobs, auditoria y notificaciones</li>
                        <li>Respuesta JSON estandar en `api/v1`</li>
                    </ul>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Siguiente bloque</div>
                    <ul class="space-y-3 text-slate-700 list-disc pl-5">
                        <li>CRUD generico backend por recurso</li>
                        <li>Tabla reutilizable con filtros, orden y paginacion</li>
                        <li>Formularios configurables por metadata</li>
                        <li>Validacion y serializacion uniformes para modulos</li>
                    </ul>
                </div>
            </div>

            <div class="col-span-12">
                <div class="rounded-3xl border border-dashed border-sky-300 bg-sky-50 p-6 text-sky-900">
                    El objetivo de esta limpieza fue retirar la falsa sensacion de que ya existia un CRUD base funcional. Lo que queda visible ahora representa el estado real del producto.
                </div>
            </div>
        </div>
    </div>
</template>
