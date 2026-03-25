<script setup>
import { authStore } from '@/core/auth/authStore';
import { computed } from 'vue';

const user = computed(() => authStore.state.user);
const activeOrganization = computed(() => user.value?.organizacion_activa ?? null);
const organizations = computed(() => user.value?.organizaciones ?? []);
const roles = computed(() => user.value?.roles ?? []);
const permissions = computed(() => user.value?.permissions ?? []);
</script>

<template>
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Core Platform</div>
                <h1 class="text-3xl font-semibold text-slate-900 mb-2">Bienvenido{{ user?.name ? `, ${user.name}` : '' }}</h1>
                <p class="text-slate-600 max-w-3xl">Esta base ya opera sobre autenticacion API, tenancy por organizacion, modulos administrables y demos tecnicas del core.</p>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Sesion activa</div>
                <div class="space-y-3 text-slate-700">
                    <div><strong>Usuario:</strong> {{ user?.name || '-' }}</div>
                    <div><strong>Correo:</strong> {{ user?.email || '-' }}</div>
                    <div><strong>Organizacion:</strong> {{ activeOrganization?.nombre || 'Sin contexto activo' }}</div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Acceso</div>
                <div class="space-y-3 text-slate-700">
                    <div><strong>Roles:</strong> {{ roles.length ? roles.join(', ') : 'Sin roles' }}</div>
                    <div><strong>Permisos:</strong> {{ permissions.length ? permissions.join(', ') : 'Sin permisos' }}</div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                <div class="text-sm font-semibold text-slate-500 uppercase mb-3">Tenancy</div>
                <div v-if="organizations.length" class="space-y-3 text-slate-700">
                    <div v-for="organization in organizations" :key="organization.id" class="rounded-2xl border border-slate-200 p-4">
                        <div class="font-semibold text-slate-900">{{ organization.nombre }}</div>
                        <div class="text-sm text-slate-600">{{ organization.slug }}</div>
                    </div>
                </div>
                <div v-else class="text-slate-500">Este usuario todavia no tiene organizaciones asociadas.</div>
            </div>
        </div>
    </div>
</template>
