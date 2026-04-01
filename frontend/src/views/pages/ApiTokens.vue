<script setup>
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import { formatDateTime } from '@/core/settings/formatters';
import { useActionFeedback } from '@/core/ui/useActionFeedback';
import api from '@/service/api';
import { useConfirm } from 'primevue/useconfirm';
import { computed, onMounted, reactive } from 'vue';

const confirm = useConfirm();
const feedback = useActionFeedback();

const state = reactive({
    loading: false,
    saving: false,
    revokingId: null,
    items: [],
    form: {
        name: '',
        expires_in_days: 30
    },
    plainTextToken: null
});

const hasItems = computed(() => state.items.length > 0);

async function loadTokens() {
    state.loading = true;

    try {
        const response = await api.get('/v1/auth/api-tokens');
        state.items = response.data.datos ?? [];
    } finally {
        state.loading = false;
    }
}

async function createToken() {
    if (state.saving) {
        return;
    }

    state.saving = true;

    try {
        const response = await api.post('/v1/auth/api-tokens', state.form);
        state.plainTextToken = response.data.datos?.plain_text_token ?? null;
        state.form = {
            name: '',
            expires_in_days: 30
        };
        await loadTokens();
        feedback.showSuccess('Token creado', 'Copia el token ahora. Luego ya no podra mostrarse otra vez.', 4000);
    } catch (error) {
        feedback.showError('No se pudo crear el token', error, 'Revisa el nombre y expiracion configurados.');
    } finally {
        state.saving = false;
    }
}

function revokeToken(token) {
    confirm.require({
        message: `Se revocara el token "${token.name}".`,
        header: 'Revocar token',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Revocar',
        rejectLabel: 'Cancelar',
        accept: async () => {
            state.revokingId = token.id;

            try {
                await api.delete(`/v1/auth/api-tokens/${token.id}`);
                await loadTokens();
                feedback.showSuccess('Token revocado', 'El acceso API ya fue revocado.');
            } finally {
                state.revokingId = null;
            }
        }
    });
}

onMounted(loadTokens);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">API Access</div>
            <h1 class="text-3xl font-semibold text-slate-900 mb-3">API Tokens</h1>
            <p class="text-slate-600 max-w-3xl">Gestiona tokens personales para integraciones o uso de la API desde terceros sin depender de la sesion interactiva del frontend.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="app-form-section">
                <div class="app-form-section-header">
                    <div class="app-form-section-title">Nuevo token personal</div>
                    <p class="app-form-section-description">Crea credenciales personales para scripts, BI o integraciones que no usen sesion interactiva del frontend.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-[2fr_1fr_auto]">
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-2">Nombre del token</label>
                        <InputText v-model="state.form.name" class="w-full" placeholder="Ej. Integracion BI o Script local" :disabled="state.saving" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-2">Expira en dias</label>
                        <InputNumber v-model="state.form.expires_in_days" class="w-full" :useGrouping="false" :disabled="state.saving" />
                    </div>
                    <div class="flex items-end">
                        <Button class="app-button-standard" label="Crear token" icon="pi pi-key" :loading="state.saving" :disabled="state.saving" @click="createToken" />
                    </div>
                </div>
            </div>

            <div v-if="state.saving" class="mt-4 text-sm text-slate-500">Generando token y actualizando el listado...</div>

            <div v-if="state.plainTextToken" class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="text-sm font-semibold text-emerald-800 mb-2">Token generado</div>
                <code class="block overflow-x-auto rounded-xl bg-white px-3 py-2 text-sm text-slate-800">{{ state.plainTextToken }}</code>
            </div>
        </div>

        <StateSkeleton v-if="state.loading" />

        <div v-else class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <StateEmpty v-if="!hasItems" title="Sin tokens API" description="Todavia no generaste tokens personales para acceso API." icon="pi pi-key" />

            <div v-else class="overflow-x-auto">
                <DataTable :value="state.items" dataKey="id">
                    <Column field="name" header="Nombre" style="min-width: 15rem" />
                    <Column field="type" header="Tipo" style="min-width: 10rem" />
                    <Column field="created_at" header="Creado" style="min-width: 12rem">
                        <template #body="slotProps">{{ formatDateTime(slotProps.data.created_at) }}</template>
                    </Column>
                    <Column field="last_used_at" header="Ultimo uso" style="min-width: 12rem">
                        <template #body="slotProps">{{ formatDateTime(slotProps.data.last_used_at) }}</template>
                    </Column>
                    <Column field="expires_at" header="Expira" style="min-width: 12rem">
                        <template #body="slotProps">{{ formatDateTime(slotProps.data.expires_at) }}</template>
                    </Column>
                    <Column header="Acciones" style="min-width: 8rem">
                        <template #body="slotProps">
                            <Button label="Revocar" icon="pi pi-times" text severity="danger" :loading="state.revokingId === slotProps.data.id" :disabled="state.revokingId !== null" @click="revokeToken(slotProps.data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>
