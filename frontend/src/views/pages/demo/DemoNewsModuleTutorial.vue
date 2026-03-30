<script setup>
import DemoPageHero from '@/components/demo/DemoPageHero.vue';
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';

const learningGoals = [
    'definir roles y permisos sin mezclar responsabilidades',
    'decidir por que la gestion interna si cabe en Data Engine',
    'usar eventos y notificaciones sin acoplar el modulo a dispositivos',
    'usar export inmediato y export async como dos patrones distintos',
    'guardar archivos con una ruta estable en Spaces desde el core',
    'aprender a pensar primero el flujo y recien despues las pantallas'
];

const steps = [
    {
        title: 'Paso 1. Arranque del modulo',
        why: 'Empezamos por estructura y contrato, no por pantallas. Eso evita que el modulo nazca desordenado.',
        action: 'Generar el scaffold minimo con `php artisan stackbase:make-module News` y completar nombre, descripcion, permisos y rutas del manifest.',
        outcome: 'Al terminar este paso ya existe un esqueleto claro del modulo y todavia no hay decisiones de UI tomadas a ciegas.'
    },
    {
        title: 'Paso 2. Roles y permisos',
        why: 'Antes de modelar la noticia, se define quien puede crear, revisar y ver. La seguridad nace antes que la UI.',
        action: 'Crear dos roles: `news-author` y `news-editor`, con permisos separados para crear, editar, aprobar, exportar y ver.',
        outcome: 'El equipo sabe desde el inicio que no cualquier usuario autenticado puede ver o manipular noticias.'
    },
    {
        title: 'Paso 3. Modelo y estados',
        why: 'Los estados ordenan el workflow y simplifican reglas. Aqui no hace falta un motor editorial complejo.',
        action: 'Modelar `noticia` con estados `draft`, `submitted`, `approved` y `rejected`. `approved` publica automaticamente.',
        outcome: 'Las transiciones del modulo quedan claras y se reduce el riesgo de meter condiciones especiales por todos lados.'
    },
    {
        title: 'Paso 4. Data Engine para gestion interna',
        why: 'La gestion interna es administrativa: listado, filtros, estados, export y accion editorial simple. Eso encaja bien en Data Engine.',
        action: 'Registrar el recurso de noticias en Data Engine, con filtros por titulo, estado, autor y fecha.',
        outcome: 'Se aprovecha una pieza transversal del stack sin inventar una interfaz compleja antes de que el dominio realmente la necesite.'
    },
    {
        title: 'Paso 5. Flujo editorial',
        why: 'El valor del modulo esta en el paso de autor a editor. Ese flujo debe vivir en servicios de aplicacion, no en botones sueltos.',
        action: 'Permitir que el Autor cree y envie. Permitir que el Editor corrija, apruebe o rechace. Aprobar llena `fecha_publicacion`.',
        outcome: 'La aprobacion deja de depender de la UI y se vuelve una regla de negocio facil de probar.'
    },
    {
        title: 'Paso 6. Imagen principal y Spaces',
        why: 'El archivo no debe escribirse directo desde el modulo. El core de archivos debe resolver nombre fisico, metadata y ruta.',
        action: 'Guardar la portada con la convension `stackbase/{env}/{organization_slug}/{module_key}/{entity_key}/{YYYY}/{MM}/{record_id}/{file_category}/{generated_filename}`.',
        outcome: 'Los archivos quedan ordenados, faciles de limpiar a futuro y consistentes con cualquier otro modulo de la plataforma.'
    },
    {
        title: 'Paso 7. Notificaciones y exports',
        why: 'Este tutorial ensena dos automatizaciones transversales muy utiles en sistemas reales.',
        action: 'Notificar al Editor al pasar a `submitted`, al Autor al pasar a `approved`, y mostrar export CSV inmediata y export CSV async con aviso al terminar.',
        outcome: 'El ejemplo ensena integracion con notificaciones y jobs sin convertir el modulo en un bloque acoplado al proveedor o al dispositivo.'
    },
    {
        title: 'Paso 8. Pagina de noticias y pruebas',
        why: 'El modulo no termina en el backoffice. Hay que mostrar la lectura final y cerrar con pruebas del flujo completo.',
        action: 'Crear una pagina autenticada con `news.view`, paginacion, filtros de lectura y pruebas de permisos, transiciones y exportaciones.',
        outcome: 'El desarrollador termina el tutorial entendiendo tanto la experiencia interna como la experiencia final del usuario lector.'
    }
];

const roleMatrix = [
    {
        role: 'news-author',
        purpose: 'Escribe y envia noticias a revision.',
        permissions: ['news.view', 'news.create', 'news.edit.own', 'news.submit']
    },
    {
        role: 'news-editor',
        purpose: 'Revisa, corrige, aprueba, rechaza y exporta.',
        permissions: ['news.view', 'news.edit.any', 'news.approve', 'news.export']
    }
];

const stateFlow = [
    {
        key: 'draft',
        meaning: 'La noticia existe, pero todavia no entra al circuito editorial.'
    },
    {
        key: 'submitted',
        meaning: 'El autor la envio y ahora necesita accion de un editor.'
    },
    {
        key: 'approved',
        meaning: 'El editor la aprueba y queda publicada automaticamente.'
    },
    {
        key: 'rejected',
        meaning: 'El editor la devuelve al autor para ajustes.'
    }
];

const fields = ['titulo', 'slug', 'resumen', 'contenido', 'imagen_principal_file_id', 'estado', 'autor_id', 'editor_id', 'fecha_envio_revision', 'fecha_aprobacion', 'fecha_publicacion'];

const filters = ['busqueda por titulo', 'estado', 'autor', 'rango de fecha'];

const events = ['news.created', 'news.submitted', 'news.approved', 'news.rejected', 'news.export.requested', 'news.export.ready'];

const mistakes = [
    'poner la logica de aprobacion directamente en la vista',
    'permitir que cualquier usuario autenticado vea o cree noticias',
    'guardar archivos concatenando rutas a mano dentro del modulo',
    'usar Data Engine para reemplazar una UI compleja cuando el dominio ya no es solo administrativo',
    'mandar notificaciones a dispositivos en vez de notificar al usuario'
];

const spacePath = 'stackbase/{env}/{organization_slug}/{module_key}/{entity_key}/{YYYY}/{MM}/{record_id}/{file_category}/{generated_filename}';
</script>

<template>
    <div class="space-y-6">
        <DemoPageHero
            eyebrow="Demo Module"
            title="Tutorial pedagogico: modulo Noticias"
            description="Este caso guiado ensena como construir un modulo real sobre StackBase sin implementarlo todavia en codigo productivo. La idea no es memorizar pasos, sino entender por que cada decision existe."
        />

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 xl:col-span-8 space-y-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="app-panel-header mb-5">
                        <div class="app-panel-header-copy">
                            <h2 class="text-xl font-semibold text-slate-900">Que aprende este tutorial</h2>
                            <p class="text-sm text-slate-600">Si un desarrollador nuevo entiende este ejemplo, ya tiene una base fuerte para construir modulos administrativos con aprobacion, archivos y notificaciones.</p>
                        </div>
                    </div>

                    <ul class="space-y-3 text-sm leading-6 text-slate-600">
                        <li v-for="goal in learningGoals" :key="goal">{{ goal }}</li>
                    </ul>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <DemoPatternGuide
                        title="Por que este ejemplo si es bueno para onboarding"
                        :use-cases="[
                            'mezcla permisos, workflow, archivos, notificaciones y dos estilos de exportacion',
                            'muestra un caso donde Data Engine alcanza, pero no fuerza todo a Data Engine',
                            'explica como pensar el modulo, no solo que archivos crear'
                        ]"
                        :avoid-when="['no copiar este patron si el dominio necesita tablero, timeline, comentarios complejos o SLA mucho mas ricos', 'no usar este tutorial como excusa para saltarse servicios de aplicacion o reglas de seguridad']"
                        :implementation-notes="['la pagina de noticias sigue siendo autenticada y requiere `news.view`', 'approved publica automaticamente; no se agrega un estado published extra']"
                    />
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="app-panel-header mb-5">
                        <div class="app-panel-header-copy">
                            <h2 class="text-xl font-semibold text-slate-900">Paso a paso con explicacion</h2>
                            <p class="text-sm text-slate-600">Cada paso responde dos preguntas: que se hace y por que se hace en ese orden.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="step in steps" :key="step.title" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-2 text-sm font-semibold text-slate-900">{{ step.title }}</div>
                            <p class="mb-2 text-sm leading-6 text-slate-700"><span class="font-semibold text-slate-900">Por que:</span> {{ step.why }}</p>
                            <p class="mb-2 text-sm leading-6 text-slate-600"><span class="font-semibold text-slate-900">Que hacer:</span> {{ step.action }}</p>
                            <p class="text-sm leading-6 text-slate-500"><span class="font-semibold text-slate-900">Resultado esperado:</span> {{ step.outcome }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <h2 class="mb-4 text-lg font-semibold text-slate-900">Roles y permisos</h2>
                            <div class="space-y-4">
                                <div v-for="role in roleMatrix" :key="role.role" class="rounded-2xl border border-slate-200 p-4">
                                    <div class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-sky-600">{{ role.role }}</div>
                                    <p class="mb-3 text-sm leading-6 text-slate-600">{{ role.purpose }}</p>
                                    <ul class="space-y-2 text-sm text-slate-600">
                                        <li v-for="permission in role.permissions" :key="permission">
                                            <code>{{ permission }}</code>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="mb-4 text-lg font-semibold text-slate-900">Estados del workflow</h2>
                            <div class="space-y-4">
                                <div v-for="state in stateFlow" :key="state.key" class="rounded-2xl border border-slate-200 p-4">
                                    <div class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">{{ state.key }}</div>
                                    <p class="text-sm leading-6 text-slate-600">{{ state.meaning }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="app-panel-header mb-5">
                        <div class="app-panel-header-copy">
                            <h2 class="text-xl font-semibold text-slate-900">Por que aqui si usamos Data Engine</h2>
                            <p class="text-sm text-slate-600">La clave pedagogica es mostrar una decision correcta, no usar Data Engine por costumbre.</p>
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="mb-3 text-sm font-semibold text-slate-900">Campos minimos del recurso</div>
                            <ul class="space-y-2 text-sm text-slate-600">
                                <li v-for="field in fields" :key="field">
                                    <code>{{ field }}</code>
                                </li>
                            </ul>
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="mb-3 text-sm font-semibold text-slate-900">Filtros y exports del ejemplo</div>
                            <ul class="space-y-2 text-sm text-slate-600">
                                <li v-for="filter in filters" :key="filter">{{ filter }}</li>
                            </ul>
                            <div class="mt-5 text-sm font-semibold text-slate-900">Lo que ensena</div>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li>CSV inmediato para volumen chico.</li>
                                <li>CSV async para volumen grande, con job y notificacion al finalizar.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="app-panel-header mb-5">
                        <div class="app-panel-header-copy">
                            <h2 class="text-xl font-semibold text-slate-900">Eventos, notificaciones y archivos</h2>
                            <p class="text-sm text-slate-600">Aqui el tutorial conecta tres capacidades transversales del core sin acoplar el modulo a implementaciones internas.</p>
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="mb-3 text-sm font-semibold text-slate-900">Eventos sugeridos</div>
                            <ul class="space-y-2 text-sm text-slate-600">
                                <li v-for="eventName in events" :key="eventName">
                                    <code>{{ eventName }}</code>
                                </li>
                            </ul>
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="mb-3 text-sm font-semibold text-slate-900">Reglas de notificacion</div>
                            <ul class="space-y-2 text-sm text-slate-600">
                                <li>Al pasar a <code>submitted</code>, avisar al editor.</li>
                                <li>Al pasar a <code>approved</code>, avisar al autor.</li>
                                <li>Al terminar la exportacion async, avisar al usuario que la pidio.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-200 p-4">
                        <div class="mb-3 text-sm font-semibold text-slate-900">Ruta recomendada en Spaces</div>
                        <pre class="overflow-x-auto rounded-2xl bg-slate-950 p-4 text-sm text-slate-100"><code>{{ spacePath }}</code></pre>
                        <p class="mt-4 text-sm leading-6 text-slate-600">
                            En el ejemplo principal usamos <code>record_id</code> simple porque es mas facil de aprender. La variante <code>record_id + uuid corto</code> se presenta como mejora futura, no como requisito base.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-4 space-y-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-slate-900">Errores comunes que este tutorial quiere evitar</h2>
                    <ul class="space-y-3 text-sm leading-6 text-slate-600">
                        <li v-for="mistake in mistakes" :key="mistake">{{ mistake }}</li>
                    </ul>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-slate-900">Checklist final</h2>
                    <ul class="space-y-3 text-sm leading-6 text-slate-600">
                        <li>La pagina de noticias requiere `news.view`.</li>
                        <li>Solo Autor y Editor pueden intervenir en el flujo editorial.</li>
                        <li>La aprobacion llena `fecha_publicacion` y publica automaticamente.</li>
                        <li>La imagen principal usa el core de archivos y la ruta de Spaces estandar.</li>
                        <li>El ejemplo incluye export inmediata y export async.</li>
                        <li>Hay al menos pruebas de permisos, estados, notificaciones y exportaciones.</li>
                    </ul>
                </div>

                <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                    <h2 class="mb-3 text-lg font-semibold text-amber-900">Cuando este ejemplo ya no alcanza</h2>
                    <p class="text-sm leading-6 text-amber-900/80">
                        Si `Noticias` empieza a necesitar calendario editorial, comentarios de revision, versionado del contenido, campanas o analitica rica, entonces deja de ser un caso para Data Engine y merece una UI propia del modulo.
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="app-panel-header mb-5">
                <div class="app-panel-header-copy">
                    <h2 class="text-xl font-semibold text-slate-900">Como leer este tutorial sin perderse</h2>
                    <p class="text-sm text-slate-600">La mejor manera de aprovecharlo no es copiar todo de una vez, sino avanzar por capas y verificar cada capa antes de seguir.</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="mb-2 text-sm font-semibold text-slate-900">Capa 1. Seguridad</div>
                    <p class="text-sm leading-6 text-slate-600">Primero permisos, roles y visibilidad. Si eso no esta claro, todo lo demas nace fragil.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="mb-2 text-sm font-semibold text-slate-900">Capa 2. Flujo</div>
                    <p class="text-sm leading-6 text-slate-600">Despues estados, aprobacion, eventos y notificaciones. Aqui se define el comportamiento real del dominio.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="mb-2 text-sm font-semibold text-slate-900">Capa 3. Superficie</div>
                    <p class="text-sm leading-6 text-slate-600">Recien entonces tiene sentido hablar de Data Engine, pagina de lectura, exportes y archivos.</p>
                </div>
            </div>
        </div>
    </div>
</template>
