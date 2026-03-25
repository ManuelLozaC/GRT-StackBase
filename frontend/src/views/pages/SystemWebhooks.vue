<script setup>
import StateEmpty from '@/components/core/StateEmpty.vue';
import StateSkeleton from '@/components/core/StateSkeleton.vue';
import { formatDateTime } from '@/core/settings/formatters';
import api from '@/service/api';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, watch } from 'vue';

const toast = useToast();

const defaultForm = () => ({
    id: null,
    module_key: '',
    event_key: '',
    target_url: '',
    signing_secret: '',
    custom_headers_text: '',
    is_active: true
});

const defaultReceiverForm = () => ({
    id: null,
    module_key: '',
    event_key: '',
    source_name: '',
    signing_secret: '',
    is_active: true
});

const state = reactive({
    loading: false,
    saving: false,
    receiverSaving: false,
    testingId: null,
    dialogVisible: false,
    receiverDialogVisible: false,
    endpoints: [],
    deliveries: [],
    receivers: [],
    receipts: [],
    catalog: [],
    form: defaultForm(),
    receiverForm: defaultReceiverForm()
});

const moduleOptions = computed(() => state.catalog);
const eventOptions = computed(() => {
    const selectedModule = state.catalog.find((moduleItem) => moduleItem.key === state.form.module_key);

    return selectedModule?.events ?? [];
});
const receiverEventOptions = computed(() => {
    const selectedModule = state.catalog.find((moduleItem) => moduleItem.key === state.receiverForm.module_key);

    return selectedModule?.events ?? [];
});

function resetForm() {
    state.form = defaultForm();
}

function openCreateDialog() {
    resetForm();
    state.dialogVisible = true;
}

function resetReceiverForm() {
    state.receiverForm = defaultReceiverForm();
}

function openCreateReceiverDialog() {
    resetReceiverForm();
    state.receiverDialogVisible = true;
}

function openEditDialog(endpoint) {
    state.form = {
        id: endpoint.id,
        module_key: endpoint.module_key,
        event_key: endpoint.event_key,
        target_url: endpoint.target_url,
        signing_secret: '',
        custom_headers_text: Object.entries(endpoint.custom_headers ?? {})
            .map(([key, value]) => `${key}: ${value}`)
            .join('\n'),
        is_active: endpoint.is_active
    };
    state.dialogVisible = true;
}

function openEditReceiverDialog(receiver) {
    state.receiverForm = {
        id: receiver.id,
        module_key: receiver.module_key,
        event_key: receiver.event_key,
        source_name: receiver.source_name,
        signing_secret: '',
        is_active: receiver.is_active
    };
    state.receiverDialogVisible = true;
}

watch(
    () => state.form.module_key,
    (moduleKey, previousModuleKey) => {
        if (moduleKey === previousModuleKey) {
            return;
        }

        if (!eventOptions.value.some((eventItem) => eventItem.key === state.form.event_key)) {
            state.form.event_key = eventOptions.value[0]?.key ?? '';
        }
    }
);
watch(
    () => state.receiverForm.module_key,
    (moduleKey, previousModuleKey) => {
        if (moduleKey === previousModuleKey) {
            return;
        }

        if (!receiverEventOptions.value.some((eventItem) => eventItem.key === state.receiverForm.event_key)) {
            state.receiverForm.event_key = receiverEventOptions.value[0]?.key ?? '';
        }
    }
);

function parseHeaders() {
    return state.form.custom_headers_text
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean)
        .reduce((headers, line) => {
            const [key, ...rest] = line.split(':');

            if (!key || rest.length === 0) {
                return headers;
            }

            return {
                ...headers,
                [key.trim()]: rest.join(':').trim()
            };
        }, {});
}

async function loadData() {
    state.loading = true;

    try {
        const [endpointsResponse, deliveriesResponse, receiversResponse, receiptsResponse] = await Promise.all([
            api.get('/v1/webhooks/endpoints'),
            api.get('/v1/webhooks/deliveries'),
            api.get('/v1/webhooks/receivers'),
            api.get('/v1/webhooks/receipts')
        ]);

        state.endpoints = endpointsResponse.data.datos ?? [];
        state.catalog = endpointsResponse.data.meta?.catalog ?? [];
        state.deliveries = deliveriesResponse.data.datos ?? [];
        state.receivers = receiversResponse.data.datos ?? [];
        state.receipts = receiptsResponse.data.datos ?? [];
    } finally {
        state.loading = false;
    }
}

async function saveEndpoint() {
    state.saving = true;
    const isEditing = Boolean(state.form.id);

    const payload = {
        module_key: state.form.module_key,
        event_key: state.form.event_key,
        target_url: state.form.target_url,
        signing_secret: state.form.signing_secret,
        custom_headers: parseHeaders(),
        is_active: state.form.is_active
    };

    try {
        if (state.form.id) {
            await api.patch(`/v1/webhooks/endpoints/${state.form.id}`, payload);
        } else {
            await api.post('/v1/webhooks/endpoints', payload);
        }

        await loadData();
        state.dialogVisible = false;
        resetForm();
        toast.add({
            severity: 'success',
            summary: isEditing ? 'Endpoint actualizado' : 'Endpoint creado',
            detail: 'La configuracion del webhook quedo guardada correctamente.',
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo guardar el endpoint',
            detail: error?.response?.data?.mensaje ?? 'Revisa el contrato del webhook y los datos enviados.',
            life: 4000
        });
    } finally {
        state.saving = false;
    }
}

async function saveReceiver() {
    state.receiverSaving = true;
    const isEditing = Boolean(state.receiverForm.id);

    const payload = {
        module_key: state.receiverForm.module_key,
        event_key: state.receiverForm.event_key,
        source_name: state.receiverForm.source_name,
        signing_secret: state.receiverForm.signing_secret,
        is_active: state.receiverForm.is_active
    };

    try {
        if (state.receiverForm.id) {
            await api.patch(`/v1/webhooks/receivers/${state.receiverForm.id}`, payload);
        } else {
            await api.post('/v1/webhooks/receivers', payload);
        }

        await loadData();
        state.receiverDialogVisible = false;
        resetReceiverForm();
        toast.add({
            severity: 'success',
            summary: isEditing ? 'Receiver actualizado' : 'Receiver creado',
            detail: 'La configuracion de recepcion quedo guardada correctamente.',
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo guardar el receiver',
            detail: error?.response?.data?.mensaje ?? 'Revisa el contrato del receiver y los datos enviados.',
            life: 4000
        });
    } finally {
        state.receiverSaving = false;
    }
}

async function testEndpoint(endpoint) {
    state.testingId = endpoint.id;

    try {
        const response = await api.post(`/v1/webhooks/endpoints/${endpoint.id}/test`, {
            payload: {
                mode: 'manual-test',
                sent_from: 'system-webhooks-ui',
                endpoint_id: endpoint.id
            }
        });

        await loadData();
        toast.add({
            severity: response.data.datos?.status === 'succeeded' ? 'success' : 'warn',
            summary: 'Prueba ejecutada',
            detail: `Estado final: ${response.data.datos?.status ?? 'unknown'}.`,
            life: 3500
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'La prueba fallo',
            detail: error?.response?.data?.mensaje ?? 'No se pudo ejecutar la prueba del webhook.',
            life: 4000
        });
    } finally {
        state.testingId = null;
    }
}

function severityFor(status) {
    return (
        {
            succeeded: 'success',
            failed: 'danger',
            pending: 'warning',
            accepted: 'success',
            rejected: 'danger'
        }[status] ?? 'contrast'
    );
}

onMounted(loadData);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="mb-3 text-sm font-semibold uppercase tracking-[0.3em] text-sky-600">Integrations</div>
                    <h1 class="mb-3 text-3xl font-semibold text-slate-900">System Webhooks</h1>
                    <p class="max-w-3xl text-slate-600">Administra endpoints salientes y receivers entrantes por tenant para reaccionar a eventos del core y sincronizar integraciones externas sin romper el contrato declarativo de StackBase.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button label="Nuevo endpoint" icon="pi pi-plus" @click="openCreateDialog" />
                    <Button label="Nuevo receiver" icon="pi pi-download" severity="secondary" outlined @click="openCreateReceiverDialog" />
                </div>
            </div>
        </div>

        <StateSkeleton v-if="state.loading" />

        <template v-else>
            <div class="grid gap-4 xl:grid-cols-[1.5fr_1fr]">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Endpoints registrados</h2>
                            <p class="text-sm text-slate-500">Cada endpoint queda aislado por tenant y usa firma HMAC con secreto cifrado.</p>
                        </div>
                        <Tag severity="contrast" :value="`${state.endpoints.length} endpoint(s)`" />
                    </div>

                    <StateEmpty v-if="!state.endpoints.length" title="Sin endpoints registrados" description="Todavia no configuraste webhooks salientes para el tenant activo." icon="pi pi-send" />

                    <div v-else class="overflow-x-auto">
                        <DataTable :value="state.endpoints" dataKey="id">
                            <Column field="module_key" header="Modulo" style="min-width: 10rem" />
                            <Column field="event_key" header="Evento" style="min-width: 15rem" />
                            <Column field="target_url" header="Destino" style="min-width: 18rem" />
                            <Column field="last_delivered_at" header="Ultimo envio" style="min-width: 12rem">
                                <template #body="slotProps">{{ formatDateTime(slotProps.data.last_delivered_at) }}</template>
                            </Column>
                            <Column header="Estado" style="min-width: 9rem">
                                <template #body="slotProps">
                                    <Tag :severity="slotProps.data.is_active ? 'success' : 'secondary'" :value="slotProps.data.is_active ? 'Activo' : 'Inactivo'" />
                                </template>
                            </Column>
                            <Column header="Acciones" style="min-width: 11rem">
                                <template #body="slotProps">
                                    <div class="flex flex-wrap gap-2">
                                        <Button label="Editar" icon="pi pi-pencil" text @click="openEditDialog(slotProps.data)" />
                                        <Button label="Probar" icon="pi pi-play" text :loading="state.testingId === slotProps.data.id" @click="testEndpoint(slotProps.data)" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Catalogo disponible</h2>
                            <p class="text-sm text-slate-500">Solo se permiten eventos declarados por el contrato modular.</p>
                        </div>
                        <Tag severity="info" :value="`${state.catalog.length} modulo(s)`" />
                    </div>

                    <StateEmpty v-if="!state.catalog.length" title="Sin eventos declarados" description="Ningun modulo expone webhooks en el contrato actual." icon="pi pi-sitemap" />

                    <div v-else class="space-y-4">
                        <div v-for="moduleItem in state.catalog" :key="moduleItem.key" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-2 text-sm font-semibold uppercase tracking-[0.25em] text-slate-500">{{ moduleItem.name }}</div>
                            <div class="space-y-3">
                                <div v-for="eventItem in moduleItem.events" :key="eventItem.key" class="rounded-2xl bg-white p-3 shadow-sm">
                                    <div class="font-semibold text-slate-900">{{ eventItem.label }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ eventItem.key }}</div>
                                    <p v-if="eventItem.description" class="mt-2 text-sm text-slate-600">{{ eventItem.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Entregas recientes</h2>
                        <p class="text-sm text-slate-500">Historial de ejecuciones para troubleshooting, auditoria y observabilidad operativa.</p>
                    </div>
                    <Tag severity="contrast" :value="`${state.deliveries.length} entrega(s)`" />
                </div>

                <StateEmpty v-if="!state.deliveries.length" title="Sin entregas registradas" description="Aun no se ejecutaron pruebas ni eventos reales hacia endpoints del tenant activo." icon="pi pi-history" />

                <div v-else class="overflow-x-auto">
                    <DataTable :value="state.deliveries" dataKey="id">
                        <Column field="module_key" header="Modulo" style="min-width: 10rem" />
                        <Column field="event_key" header="Evento" style="min-width: 14rem" />
                        <Column field="target_url" header="Destino" style="min-width: 16rem" />
                        <Column field="status" header="Estado" style="min-width: 9rem">
                            <template #body="slotProps">
                                <Tag :severity="severityFor(slotProps.data.status)" :value="slotProps.data.status" />
                            </template>
                        </Column>
                        <Column field="response_status" header="HTTP" style="min-width: 7rem" />
                        <Column field="delivered_at" header="Entregado" style="min-width: 12rem">
                            <template #body="slotProps">{{ formatDateTime(slotProps.data.delivered_at) }}</template>
                        </Column>
                        <Column field="request_id" header="Request ID" style="min-width: 15rem" />
                    </DataTable>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[1.5fr_1fr]">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Receivers entrantes</h2>
                            <p class="text-sm text-slate-500">Cada receiver expone un endpoint publico controlado por firma HMAC y aislamiento por tenant.</p>
                        </div>
                        <Tag severity="contrast" :value="`${state.receivers.length} receiver(s)`" />
                    </div>

                    <StateEmpty v-if="!state.receivers.length" title="Sin receivers configurados" description="Todavia no habilitaste recepcion de webhooks para el tenant activo." icon="pi pi-download" />

                    <div v-else class="overflow-x-auto">
                        <DataTable :value="state.receivers" dataKey="id">
                            <Column field="source_name" header="Origen" style="min-width: 12rem" />
                            <Column field="module_key" header="Modulo" style="min-width: 10rem" />
                            <Column field="event_key" header="Evento" style="min-width: 15rem" />
                            <Column header="Endpoint publico" style="min-width: 18rem">
                                <template #body="slotProps">
                                    <code>/api/v1/webhooks/incoming/{{ slotProps.data.id }}</code>
                                </template>
                            </Column>
                            <Column field="last_received_at" header="Ultima recepcion" style="min-width: 12rem">
                                <template #body="slotProps">{{ formatDateTime(slotProps.data.last_received_at) }}</template>
                            </Column>
                            <Column header="Estado" style="min-width: 9rem">
                                <template #body="slotProps">
                                    <Tag :severity="slotProps.data.is_active ? 'success' : 'secondary'" :value="slotProps.data.is_active ? 'Activo' : 'Inactivo'" />
                                </template>
                            </Column>
                            <Column header="Acciones" style="min-width: 8rem">
                                <template #body="slotProps">
                                    <Button label="Editar" icon="pi pi-pencil" text @click="openEditReceiverDialog(slotProps.data)" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Recepciones recientes</h2>
                            <p class="text-sm text-slate-500">Valida firmas, origen, request ID y estado de procesamiento.</p>
                        </div>
                        <Tag severity="info" :value="`${state.receipts.length} item(s)`" />
                    </div>

                    <StateEmpty v-if="!state.receipts.length" title="Sin recepciones" description="Aun no llegaron eventos externos a los receivers del tenant activo." icon="pi pi-inbox" />

                    <div v-else class="space-y-3">
                        <div v-for="receipt in state.receipts.slice(0, 8)" :key="receipt.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ receipt.source_name || 'Origen externo' }}</div>
                                    <div class="text-sm text-slate-500">{{ receipt.module_key }} / {{ receipt.event_key }}</div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Tag :severity="receipt.signature_status === 'valid' ? 'success' : 'danger'" :value="`Firma ${receipt.signature_status}`" />
                                    <Tag :severity="severityFor(receipt.processing_status)" :value="receipt.processing_status" />
                                </div>
                            </div>
                            <div class="mt-3 grid gap-2 text-sm text-slate-600">
                                <div><strong>Fecha:</strong> {{ formatDateTime(receipt.received_at) }}</div>
                                <div><strong>Request ID:</strong> {{ receipt.request_id || 'n/a' }}</div>
                                <div><strong>IP:</strong> {{ receipt.ip_address || 'n/a' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <Dialog v-model:visible="state.dialogVisible" modal :header="state.form.id ? 'Editar webhook' : 'Nuevo webhook'" :style="{ width: '42rem' }" @hide="resetForm">
            <div class="grid gap-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">Modulo</label>
                    <Select v-model="state.form.module_key" :options="moduleOptions" optionLabel="name" optionValue="key" class="w-full" placeholder="Selecciona un modulo" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">Evento</label>
                    <Select v-model="state.form.event_key" :options="eventOptions" optionLabel="label" optionValue="key" class="w-full" placeholder="Selecciona un evento" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">Target URL</label>
                    <InputText v-model="state.form.target_url" class="w-full" placeholder="https://tu-sistema.test/webhooks/stackbase" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">
                        {{ state.form.id ? 'Nuevo secreto (opcional)' : 'Secreto de firma' }}
                    </label>
                    <Password v-model="state.form.signing_secret" class="w-full" inputClass="w-full" :feedback="false" toggleMask />
                    <small class="mt-2 block text-slate-500">Si dejas este campo vacio al editar, se conserva el secreto actual.</small>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">Headers custom</label>
                    <Textarea v-model="state.form.custom_headers_text" class="w-full" rows="4" placeholder="X-App: StackBase&#10;X-Environment: staging" />
                </div>
                <div class="flex items-center gap-3">
                    <ToggleSwitch v-model="state.form.is_active" />
                    <span class="text-sm text-slate-600">Endpoint activo</span>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" severity="secondary" outlined @click="state.dialogVisible = false" />
                <Button label="Guardar webhook" :loading="state.saving" @click="saveEndpoint" />
            </template>
        </Dialog>

        <Dialog v-model:visible="state.receiverDialogVisible" modal :header="state.receiverForm.id ? 'Editar receiver' : 'Nuevo receiver'" :style="{ width: '38rem' }" @hide="resetReceiverForm">
            <div class="grid gap-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">Modulo</label>
                    <Select v-model="state.receiverForm.module_key" :options="moduleOptions" optionLabel="name" optionValue="key" class="w-full" placeholder="Selecciona un modulo" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">Evento</label>
                    <Select v-model="state.receiverForm.event_key" :options="receiverEventOptions" optionLabel="label" optionValue="key" class="w-full" placeholder="Selecciona un evento" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">Nombre del origen</label>
                    <InputText v-model="state.receiverForm.source_name" class="w-full" placeholder="Ej. ERP, CRM, partner o bot externo" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">
                        {{ state.receiverForm.id ? 'Nuevo secreto (opcional)' : 'Secreto de firma' }}
                    </label>
                    <Password v-model="state.receiverForm.signing_secret" class="w-full" inputClass="w-full" :feedback="false" toggleMask />
                    <small class="mt-2 block text-slate-500">Si lo dejas vacio al editar, se conserva el secreto actual.</small>
                </div>
                <div class="flex items-center gap-3">
                    <ToggleSwitch v-model="state.receiverForm.is_active" />
                    <span class="text-sm text-slate-600">Receiver activo</span>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" severity="secondary" outlined @click="state.receiverDialogVisible = false" />
                <Button label="Guardar receiver" :loading="state.receiverSaving" @click="saveReceiver" />
            </template>
        </Dialog>
    </div>
</template>
