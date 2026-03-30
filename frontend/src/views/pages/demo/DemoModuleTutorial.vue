<script setup>
import DemoPageHero from '@/components/demo/DemoPageHero.vue';
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';

const steps = [
    {
        title: '1. Crear el scaffold minimo',
        detail: 'Ejecuta `php artisan stackbase:make-module Leads` para generar provider, manifest del modulo, registry frontend y documento base. No crea dominio ni UI compleja por su cuenta.'
    },
    {
        title: '2. Registrar el dominio real',
        detail: 'Agrega modelos, migraciones, servicios y reglas del negocio dentro del modulo. El scaffold no reemplaza analisis del dominio.'
    },
    {
        title: '3. Decidir Data Engine vs UX propia',
        detail: 'Usa Data Engine si la entidad es CRUD administrativo. Si tiene SLA, estados, timeline, bandeja, aprobaciones o tablero operativo, construye una interfaz propia del modulo.'
    },
    {
        title: '4. Declarar permisos y rutas',
        detail: 'El manifest del modulo es la fuente de verdad. Declara permisos cortos y consistentes, y protege pantallas o endpoints sensibles.'
    },
    {
        title: '5. Agregar notificaciones, jobs y eventos solo si aportan valor',
        detail: 'Usa eventos de dominio para hechos importantes. Usa jobs para tareas lentas o SLA. El core se encarga de canales, colas y trazabilidad.'
    },
    {
        title: '6. Cerrar con tests y demo',
        detail: 'Todo patron reusable debe dejar ejemplo en Demo Module y pruebas minimas para auth, permisos, tenancy y caso feliz.'
    }
];

const decisionMatrix = [
    {
        kind: 'Data Engine',
        when: 'Catálogos, parámetros, tablas maestras, personas, áreas, cargos y CRUD administrativo simple.',
        avoid: 'No usarlo si la entidad vive más por workflow que por campos.'
    },
    {
        kind: 'UI propia del módulo',
        when: 'Leads, tickets, cobranzas, aprobaciones o cualquier flujo con SLA, timeline, estados o acciones frecuentes.',
        avoid: 'No construir pantalla custom si todavía es solo un CRUD sin comportamiento especial.'
    }
];
</script>

<template>
    <div class="space-y-6">
        <DemoPageHero
            eyebrow="Demo Module"
            title="Tutorial para crear un módulo nuevo"
            description="Guía práctica para arrancar un módulo sobre StackBase sin convertir el core en un Frankenstein. Esta vista resume el camino recomendado, los límites del scaffold y cómo decidir entre Data Engine y UI propia."
        />

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 xl:col-span-8 space-y-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="app-panel-header mb-5">
                        <div class="app-panel-header-copy">
                            <h2 class="text-xl font-semibold text-slate-900">Flujo recomendado</h2>
                            <p class="text-sm text-slate-600">El orden importa: primero estructura y permisos, luego dominio, después UX y automatizaciones.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="step in steps" :key="step.title" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-2 text-sm font-semibold text-slate-900">{{ step.title }}</div>
                            <p class="text-sm leading-6 text-slate-600">{{ step.detail }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="app-panel-header mb-5">
                        <div class="app-panel-header-copy">
                            <h2 class="text-xl font-semibold text-slate-900">Comandos disponibles</h2>
                            <p class="text-sm text-slate-600">Los generadores son deliberadamente pequeños. Sirven para empezar parejo, no para adivinar negocio.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <pre class="overflow-x-auto rounded-2xl bg-slate-950 p-4 text-sm text-slate-100"><code>php artisan stackbase:make-module Leads
php artisan stackbase:make-data-resource leads lead-card "App\\Modules\\Leads\\Models\\LeadCard" --search</code></pre>

                        <DemoPatternGuide
                            title="Qué generan y qué no"
                            :use-cases="['Provider, manifest del módulo, registry frontend y base documental.', 'Scaffold controlado de recurso Data Engine con campos conservadores.']"
                            :avoid-when="['No crean migraciones ni modelo de negocio real.', 'No deben usarse para esconder decisiones de arquitectura o UX.']"
                            :implementation-notes="['El core carga manifests por módulo y recursos por archivo, evitando tocar configs gigantes manualmente.', 'Si un caso deja de ser CRUD administrativo, se migra a pantalla propia del módulo.']"
                        />
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="app-panel-header mb-5">
                        <div class="app-panel-header-copy">
                            <h2 class="text-xl font-semibold text-slate-900">Data Engine o interfaz propia</h2>
                            <p class="text-sm text-slate-600">Esta decisión es la más importante para mantener simple el stack.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div v-for="item in decisionMatrix" :key="item.kind" class="rounded-2xl border border-slate-200 p-5">
                            <div class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-sky-600">{{ item.kind }}</div>
                            <p class="mb-3 text-sm leading-6 text-slate-700"><span class="font-semibold text-slate-900">Cuando sí:</span> {{ item.when }}</p>
                            <p class="text-sm leading-6 text-slate-600"><span class="font-semibold text-slate-900">Cuando no:</span> {{ item.avoid }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-4 space-y-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-slate-900">Checklist de salida</h2>
                    <ul class="space-y-3 text-sm leading-6 text-slate-600">
                        <li>Manifest backend y registry frontend listos.</li>
                        <li>Permisos mínimos declarados y protegidos.</li>
                        <li>Entidad evaluada: Data Engine o pantalla propia.</li>
                        <li>Eventos de dominio solo para hechos importantes.</li>
                        <li>Jobs, notificaciones y webhooks solo cuando agregan valor.</li>
                        <li>Tests mínimos y demo/referencia del patrón.</li>
                    </ul>
                </div>

                <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                    <h2 class="mb-3 text-lg font-semibold text-amber-900">Regla anti Frankenstein</h2>
                    <p class="text-sm leading-6 text-amber-900/80">Si una automatización genera demasiada magia, toca demasiadas capas o cuesta mucho explicarla a un desarrollador nuevo, no debería entrar al core.</p>
                </div>
            </div>
        </div>
    </div>
</template>
