<script setup>
import { useAuthStore } from '@/stores/auth';
import { computed } from 'vue';

const authStore = useAuthStore();

const usuario = computed(() => authStore.usuario);
const asignaciones = computed(() => usuario.value?.asignaciones_laborales ?? []);
</script>

<template>
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Stack Base 2026</div>
                <h1 class="text-3xl font-semibold text-slate-900 mb-2">Bienvenido{{ usuario?.nombre_mostrar ? `, ${usuario.nombre_mostrar}` : '' }}</h1>
                <p class="text-slate-600 max-w-3xl">Esta es la base inicial del panel administrativo. Desde aquí vamos a consolidar autenticación, usuarios, oficinas, asignaciones laborales y permisos por sucursal.</p>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Usuario autenticado</div>
                <div class="space-y-3 text-slate-700">
                    <div><strong>Alias:</strong> {{ usuario?.alias || '-' }}</div>
                    <div><strong>Correo:</strong> {{ usuario?.email || '-' }}</div>
                    <div><strong>Superusuario:</strong> {{ usuario?.es_superusuario ? 'Sí' : 'No' }}</div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                <div class="text-sm font-semibold text-slate-500 uppercase mb-4">Asignaciones por oficina</div>

                <div v-if="!asignaciones.length" class="text-slate-500">El usuario todavía no tiene asignaciones laborales registradas.</div>

                <div v-else class="space-y-4">
                    <div v-for="asignacion in asignaciones" :key="asignacion.id" class="rounded-2xl border border-slate-200 p-4">
                        <div class="font-semibold text-slate-900">Oficina ID {{ asignacion.oficina_id }}</div>
                        <div class="text-sm text-slate-600 mt-2">Área ID: {{ asignacion.area_id || 'Sin área' }}</div>
                        <div class="text-sm text-slate-600">Cargo ID: {{ asignacion.cargo_id || 'Sin cargo' }}</div>
                        <div class="text-sm text-slate-600">Principal: {{ asignacion.es_principal ? 'Sí' : 'No' }}</div>
                        <div class="text-sm text-slate-600">Activa: {{ asignacion.activa ? 'Sí' : 'No' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
