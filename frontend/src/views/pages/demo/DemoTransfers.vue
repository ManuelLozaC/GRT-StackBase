<script setup>
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';
import api from '@/service/api';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive } from 'vue';

const toast = useToast();

const exportFormats = [
    { label: 'CSV', value: 'csv' },
    { label: 'Excel', value: 'excel' },
    { label: 'PDF', value: 'pdf' }
];

const exportModes = [
    { label: 'Directo', value: 'sync' },
    { label: 'Async', value: 'async' }
];

const state = reactive({
    loading: false,
    exporting: false,
    importing: false,
    downloading: false,
    transferRuns: [],
    importFile: null,
    exportFormat: 'csv',
    exportMode: 'sync'
});

const completedRuns = computed(() => state.transferRuns.filter((run) => run.status === 'completed'));

async function loadTransfers() {
    state.loading = true;

    try {
        const response = await api.get('/v1/data/demo-contacts/transfers');
        state.transferRuns = response.data.datos ?? [];
    } finally {
        state.loading = false;
    }
}

function onImportFileChange(event) {
    state.importFile = event.target.files?.[0] ?? null;
}

async function exportData() {
    state.exporting = true;

    try {
        if (state.exportMode === 'async') {
            const response = await api.get('/v1/data/demo-contacts/export', {
                params: {
                    format: state.exportFormat,
                    mode: 'async'
                }
            });

            await loadTransfers();

            toast.add({
                severity: 'success',
                summary: 'Exportacion encolada',
                detail: `Se registro la corrida ${response.data.datos?.uuid ?? ''}. Procesala con el worker de cola para generar el archivo.`,
                life: 4000
            });

            return;
        }

        await downloadBlob('/v1/data/demo-contacts/export', {
            format: state.exportFormat,
            mode: 'sync'
        });

        await loadTransfers();

        toast.add({
            severity: 'success',
            summary: 'Exportacion completada',
            detail: `Se descargo el archivo ${state.exportFormat.toUpperCase()} correctamente.`,
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo exportar',
            detail: error?.response?.data?.mensaje ?? 'No se pudo generar la exportacion solicitada.',
            life: 4500
        });
    } finally {
        state.exporting = false;
    }
}

async function importCsv() {
    if (!state.importFile) {
        return;
    }

    state.importing = true;

    try {
        const formData = new FormData();
        formData.append('file', state.importFile);

        const response = await api.post('/v1/data/demo-contacts/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        state.importFile = null;
        await loadTransfers();

        toast.add({
            severity: response.data.datos?.records_failed > 0 ? 'warn' : 'success',
            summary: response.data.datos?.records_failed > 0 ? 'Importacion parcial' : 'Importacion completada',
            detail:
                response.data.datos?.records_failed > 0
                    ? `Se importaron ${response.data.datos.records_processed} filas y ${response.data.datos.records_failed} fallaron.`
                    : `Se importaron ${response.data.datos?.records_processed ?? 0} filas correctamente.`,
            life: 4000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo importar',
            detail: error?.response?.data?.errores?.file?.[0] ?? error?.response?.data?.mensaje ?? 'Revisa el archivo CSV e intenta otra vez.',
            life: 4500
        });
    } finally {
        state.importing = false;
    }
}

async function downloadRun(run) {
    state.downloading = true;

    try {
        await downloadBlob(`/v1/data/transfers/${run.uuid}/download`);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo descargar',
            detail: error?.response?.data?.mensaje ?? 'El artefacto ya no esta disponible.',
            life: 4000
        });
    } finally {
        state.downloading = false;
    }
}

async function downloadBlob(url, params = {}) {
    const response = await api.get(url, {
        params,
        responseType: 'blob'
    });

    const disposition = response.headers['content-disposition'] ?? '';
    const match = disposition.match(/filename="?([^"]+)"?/i);
    const fileName = match?.[1] ?? 'download.bin';
    const blobUrl = window.URL.createObjectURL(new Blob([response.data], { type: response.headers['content-type'] }));
    const link = document.createElement('a');

    link.href = blobUrl;
    link.setAttribute('download', fileName);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(blobUrl);
}

onMounted(loadTransfers);
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="text-sm uppercase tracking-[0.3em] text-sky-600 font-semibold mb-3">Demo Module</div>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-slate-900 mb-3">Transfers Demo</h1>
                    <p class="text-slate-600 max-w-3xl">
                        Esta demo valida exportaciones `CSV / Excel / PDF`, importacion CSV e historial de corridas del `Data Engine`. El modo `async` deja la corrida en cola y requiere un worker activo para completar el archivo.
                    </p>
                </div>
                <Tag severity="info" :value="`${state.transferRuns.length} corrida${state.transferRuns.length === 1 ? '' : 's'}`" />
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 xl:col-span-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <h2 class="text-xl font-semibold text-slate-900 mb-4">Exportar demo-contacts</h2>
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 md:col-span-6">
                            <label class="block text-sm font-semibold text-slate-600 mb-2">Formato</label>
                            <Select v-model="state.exportFormat" :options="exportFormats" optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="block text-sm font-semibold text-slate-600 mb-2">Modo</label>
                            <Select v-model="state.exportMode" :options="exportModes" optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                    </div>
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600 mt-4">Para probar `async`, levanta un worker en backend con `php artisan queue:work --queue=data-exports,demo`.</div>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <Button class="app-button-standard" :label="state.exportMode === 'async' ? 'Encolar exportacion' : 'Descargar ahora'" icon="pi pi-download" :loading="state.exporting" @click="exportData" />
                        <Button class="app-button-standard" label="Refrescar historial" severity="secondary" outlined icon="pi pi-refresh" :loading="state.loading" @click="loadTransfers" />
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-full">
                    <h2 class="text-xl font-semibold text-slate-900 mb-4">Importar CSV</h2>
                    <p class="text-sm text-slate-600 mb-4">La importacion usa la metadata del recurso para validar columnas y reglas. Encabezados esperados: `nombre,email,telefono,empresa,estado,prioridad,notas`.</p>
                    <input type="file" accept=".csv,text/csv" class="block w-full text-sm text-slate-600" @change="onImportFileChange" />
                    <div class="mt-3 text-sm text-slate-500">
                        {{ state.importFile ? `Seleccionado: ${state.importFile.name}` : 'Todavia no seleccionaste un archivo.' }}
                    </div>
                    <div class="mt-5">
                        <Button class="app-button-standard" label="Importar CSV" icon="pi pi-upload" :disabled="!state.importFile" :loading="state.importing" @click="importCsv" />
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="app-panel-header">
                <div class="app-panel-header-copy">
                    <h2 class="text-xl font-semibold text-slate-900 mb-1">Historial de transferencias</h2>
                    <p class="text-sm text-slate-600 m-0">Las corridas se registran por tenant, incluyendo formato, modo, conteos y errores.</p>
                </div>
                <div class="app-panel-actions">
                    <Tag severity="contrast" :value="`${completedRuns.length} completada${completedRuns.length === 1 ? '' : 's'}`" />
                </div>
            </div>

            <DataTable :value="state.transferRuns" dataKey="uuid" :loading="state.loading">
                <Column field="type" header="Tipo" style="min-width: 7rem">
                    <template #body="slotProps">
                        <Tag :severity="slotProps.data.type === 'export' ? 'info' : 'warning'" :value="slotProps.data.type" />
                    </template>
                </Column>
                <Column field="format" header="Formato" style="min-width: 8rem" />
                <Column field="mode" header="Modo" style="min-width: 8rem" />
                <Column field="status" header="Estado" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Tag
                            :severity="
                                {
                                    completed: 'success',
                                    completed_with_errors: 'warning',
                                    queued: 'info',
                                    processing: 'info',
                                    failed: 'danger'
                                }[slotProps.data.status] ?? 'contrast'
                            "
                            :value="slotProps.data.status"
                        />
                    </template>
                </Column>
                <Column field="file_name" header="Archivo" style="min-width: 15rem" />
                <Column field="records_processed" header="Procesados" style="min-width: 8rem" />
                <Column field="records_failed" header="Errores" style="min-width: 7rem" />
                <Column field="created_at" header="Creado" style="min-width: 14rem">
                    <template #body="slotProps">
                        {{ slotProps.data.created_at ? new Date(slotProps.data.created_at).toLocaleString() : 'Sin dato' }}
                    </template>
                </Column>
                <Column header="Acciones" style="width: 10rem">
                    <template #body="slotProps">
                        <Button
                            v-if="slotProps.data.type === 'export' && slotProps.data.mode === 'async' && slotProps.data.download_url"
                            icon="pi pi-download"
                            severity="secondary"
                            text
                            rounded
                            :loading="state.downloading"
                            @click="downloadRun(slotProps.data)"
                        />
                        <span v-else class="text-sm text-slate-400">Sin descarga</span>
                    </template>
                </Column>
                <template #empty>
                    <div class="py-10 text-center text-slate-500">Todavia no hay corridas registradas para este recurso demo.</div>
                </template>
            </DataTable>
        </div>

        <DemoPatternGuide
            title="Guia para transfers, import y export"
            :when-to-use="['cuando un recurso necesita exportaciones repetibles y auditables', 'cuando la importacion debe validarse segun metadata real del recurso', 'cuando conviene ofrecer modo sync y async para distintos tamaños de trabajo']"
            :avoid-when="['cuando el volumen es minimo y no justifica historial ni corridas', 'cuando una importacion no tiene reglas claras de validacion o columnas esperadas', 'cuando el modo async se expone sin worker ni observabilidad minima']"
            :wiring="['usar transfer runs como fuente de verdad del historial operativo', 'ofrecer descarga directa para sync y artefacto diferido para async', 'describir con claridad encabezados y reglas esperadas antes de importar']"
            :notes="['esta demo ya conversa con el Data Engine real del core', 'si la exportacion pesa mucho, conectarla tambien con async patterns y jobs']"
        />
    </div>
</template>
