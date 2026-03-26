<script setup>
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';

const contentBlocks = [
    {
        title: 'Resumen ejecutivo',
        body: 'Usa este patron para abrir pantallas de administracion con una explicacion corta, concreta y orientada a accion.'
    },
    {
        title: 'Nota operativa',
        body: 'Sirve para aclarar condiciones de negocio, dependencias entre procesos o efectos secundarios antes de una accion.'
    },
    {
        title: 'Ayuda contextual',
        body: 'Ideal para acompanar formularios o dashboards donde el usuario nuevo necesita entender el por que de una seccion.'
    }
];

const checklist = ['titulos claros y orientados a tarea', 'subtitulos que reduzcan ambiguedad', 'parrafos cortos y densos en informacion', 'bloques auxiliares para avisos y contexto', 'listas breves para decisiones o pasos'];
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / UI Typography" class="w-fit" />
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <h2 class="m-0">Tipografia, contenido y bloques editoriales</h2>
                        <p class="m-0 text-color-secondary">Esta demo sirve para fijar el tono visual y editorial de las pantallas administrativas: jerarquia de titulos, parrafos, notas, listas y bloques de ayuda.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Tag value="Headings" severity="success" />
                        <Tag value="Help copy" severity="info" />
                        <Tag value="Notes" severity="warn" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-7">
            <div class="card flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <h1 class="m-0">Titulo principal de una pantalla</h1>
                    <h3 class="m-0">Subtitulo para seccion o contexto operativo</h3>
                    <p class="m-0 text-color-secondary">Este parrafo representa el nivel de densidad recomendado para una introduccion de pantalla. Debe explicar el objetivo, reducir dudas y orientar rapidamente la accion del usuario.</p>
                </div>

                <div class="typography-grid">
                    <article v-for="block in contentBlocks" :key="block.title" class="typography-card">
                        <div class="font-semibold mb-2">{{ block.title }}</div>
                        <p class="m-0 text-sm text-color-secondary">{{ block.body }}</p>
                    </article>
                </div>

                <div class="typography-note typography-note-info">
                    <strong>Consejo editorial:</strong>
                    prioriza frases directas, verbos accionables y contexto suficiente para que el usuario no tenga que adivinar el siguiente paso.
                </div>

                <div class="typography-note typography-note-warn">
                    <strong>Evita:</strong>
                    bloques demasiado largos, texto decorativo sin utilidad y labels ambiguos como "General", "Datos" o "Configuracion" sin contexto.
                </div>

                <DemoPatternGuide
                    title="Guia para contenido y tipografia"
                    :when-to-use="['cuando la pantalla necesita orientar y no solo mostrar datos', 'cuando hay decisiones o contexto que el usuario debe entender antes de actuar', 'cuando el modulo servira tambien para onboarding o soporte']"
                    :avoid-when="['cuando el texto compite con una tarea simple y directa', 'cuando se agregan parrafos largos solo para llenar espacio', 'cuando los titulos son vagos y no explican la funcion de la pantalla']"
                    :wiring="[
                        'usar una jerarquia clara: titulo, subtitulo, texto auxiliar y bloques de nota',
                        'mantener el contenido corto, accionable y orientado a la tarea',
                        'combinar texto editorial con tags, chips o bloques tecnicos cuando ayude'
                    ]"
                    :notes="['la calidad del contenido es parte del producto, no un detalle posterior', 'si una pantalla necesita mucha explicacion, revisar tambien su complejidad']"
                />
            </div>
        </div>

        <div class="col-span-12 xl:col-span-5">
            <div class="card flex flex-col gap-4 h-full">
                <div>
                    <h3 class="m-0 mb-2">Checklist de contenido</h3>
                    <p class="m-0 text-sm text-color-secondary">Guia rapida para revisar la claridad de una pantalla antes de darla por buena.</p>
                </div>

                <ul class="typography-list">
                    <li v-for="item in checklist" :key="item">{{ item }}</li>
                </ul>

                <div class="typography-code">
                    <div class="font-semibold mb-2">Ejemplo de bloque tecnico</div>
                    <pre class="m-0">
Tenant activo: GRT SRL
Oficina: TalentHub
Contexto: aprobacion comercial</pre
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.typography-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
}

.typography-card,
.typography-code {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-ground);
    padding: 1rem;
}

.typography-note {
    border-radius: 1rem;
    padding: 1rem;
    font-size: 0.95rem;
}

.typography-note-info {
    background: #e0f2fe;
    color: #0c4a6e;
}

.typography-note-warn {
    background: #fef3c7;
    color: #92400e;
}

.typography-list {
    margin: 0;
    padding-left: 1.15rem;
    display: grid;
    gap: 0.55rem;
    color: var(--text-color-secondary);
}
</style>
