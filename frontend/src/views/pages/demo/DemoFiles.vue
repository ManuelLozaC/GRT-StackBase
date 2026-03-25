<script setup>
import api from '@/service/api';
import { onMounted, reactive, ref } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const state = reactive({
    loading: false,
    uploading: false,
    downloadingUuid: null,
    generatingUuid: null,
    files: [],
    downloads: []
});

const selectedFile = ref(null);
const notes = ref('');
const temporaryLinks = ref({});

async function loadData() {
    state.loading = true;

    try {
        const [filesResponse, downloadsResponse] = await Promise.all([api.get('/v1/demo/files'), api.get('/v1/demo/files/downloads')]);

        state.files = filesResponse.data.datos ?? [];
        state.downloads = downloadsResponse.data.datos ?? [];
    } finally {
        state.loading = false;
    }
}

function onSelectFile(event) {
    selectedFile.value = event.target.files?.[0] ?? null;
}

async function uploadFile() {
    if (!selectedFile.value) {
        toast.add({
            severity: 'warn',
            summary: 'Archivo requerido',
            detail: 'Selecciona un archivo antes de subirlo.',
            life: 2500
        });

        return;
    }

    state.uploading = true;

    try {
        const formData = new FormData();
        formData.append('file', selectedFile.value);
        formData.append('notes', notes.value);

        await api.post('/v1/demo/files', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        selectedFile.value = null;
        notes.value = '';
        const input = document.getElementById('demo-file-input');

        if (input) {
            input.value = '';
        }

        toast.add({
            severity: 'success',
            summary: 'Archivo cargado',
            detail: 'La demo guardo el archivo y lo indexo en el historial.',
            life: 2500
        });

        await loadData();
    } finally {
        state.uploading = false;
    }
}

async function downloadFile(file) {
    state.downloadingUuid = file.uuid;

    try {
        const response = await api.get(`/v1/demo/files/${file.uuid}/download`, {
            responseType: 'blob'
        });

        const url = window.URL.createObjectURL(response.data);
        const link = document.createElement('a');
        link.href = url;
        link.download = file.original_name;
        link.click();
        window.URL.revokeObjectURL(url);

        toast.add({
            severity: 'success',
            summary: 'Descarga iniciada',
            detail: 'La descarga directa quedo registrada en el historial.',
            life: 2500
        });

        await loadData();
    } finally {
        state.downloadingUuid = null;
    }
}

async function generateTemporaryLink(file) {
    state.generatingUuid = file.uuid;

    try {
        const response = await api.post(`/v1/demo/files/${file.uuid}/temporary-link`, {
            ttl_minutes: 30
        });

        temporaryLinks.value[file.uuid] = response.data.datos;

        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(response.data.datos.url);
        }

        toast.add({
            severity: 'success',
            summary: 'Link temporal listo',
            detail: 'Se genero un signed URL de 30 minutos y se copio al portapapeles si estuvo disponible.',
            life: 3000
        });
    } finally {
        state.generatingUuid = null;
    }
}

function formatBytes(bytes) {
    if (!bytes) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / 1024 ** exponent;

    return `${value.toFixed(value >= 10 || exponent === 0 ? 0 : 1)} ${units[exponent]}`;
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('es-BO', {
        dateStyle: 'medium',
        timeStyle: 'short'
    }).format(new Date(value));
}

onMounted(loadData);
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / Files" class="w-fit" />
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h2 class="m-0">Demo funcional de archivos</h2>
                        <p class="m-0 text-color-secondary">Esta demo valida carga, almacenamiento, descarga directa, signed URLs e historial por usuario dentro del tenant activo.</p>
                    </div>
                    <div class="demo-summary">
                        <div>
                            <strong>{{ state.files.length }}</strong>
                            <span>archivos</span>
                        </div>
                        <div>
                            <strong>{{ state.downloads.length }}</strong>
                            <span>descargas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="card flex flex-col gap-4">
                <div>
                    <h3 class="m-0 mb-2">Subir archivo</h3>
                    <p class="m-0 text-sm text-color-secondary">Guarda el archivo en el storage configurado y registra metadata base en el core.</p>
                </div>

                <input id="demo-file-input" type="file" @change="onSelectFile" />

                <textarea v-model="notes" rows="4" class="demo-textarea" placeholder="Notas opcionales para esta carga demo"></textarea>

                <button class="demo-primary-button" :disabled="state.uploading" @click="uploadFile">
                    {{ state.uploading ? 'Subiendo...' : 'Subir archivo demo' }}
                </button>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="m-0">Archivos disponibles</h3>
                        <p class="m-0 text-sm text-color-secondary">Listado del tenant activo con token de version para trazabilidad.</p>
                    </div>
                    <button class="demo-secondary-button" :disabled="state.loading" @click="loadData">
                        {{ state.loading ? 'Actualizando...' : 'Actualizar' }}
                    </button>
                </div>

                <div v-if="state.files.length === 0" class="demo-empty-state">Todavia no hay archivos en esta organizacion. Sube uno para probar el flujo completo.</div>

                <div v-else class="demo-file-list">
                    <article v-for="file in state.files" :key="file.uuid" class="demo-file-card">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold">{{ file.original_name }}</div>
                                    <div class="text-sm text-color-secondary">{{ formatBytes(file.size_bytes) }} · {{ file.mime_type || 'tipo no detectado' }}</div>
                                </div>
                                <Tag severity="contrast" :value="`v${file.version}`" />
                            </div>

                            <div class="text-sm text-color-secondary">Subido por {{ file.uploaded_by || 'sistema' }} · {{ formatDate(file.uploaded_at) }}</div>

                            <div class="demo-token-row">
                                <span>Token de rastreo</span>
                                <code>{{ file.security_token }}</code>
                            </div>

                            <div class="demo-actions">
                                <button class="demo-primary-button" :disabled="state.downloadingUuid === file.uuid" @click="downloadFile(file)">
                                    {{ state.downloadingUuid === file.uuid ? 'Descargando...' : 'Descarga directa' }}
                                </button>
                                <button class="demo-secondary-button" :disabled="state.generatingUuid === file.uuid" @click="generateTemporaryLink(file)">
                                    {{ state.generatingUuid === file.uuid ? 'Generando...' : 'Signed URL' }}
                                </button>
                            </div>

                            <div v-if="temporaryLinks[file.uuid]" class="demo-link-box">
                                <div class="text-sm font-medium">Link temporal activo hasta {{ formatDate(temporaryLinks[file.uuid].expires_at) }}</div>
                                <input :value="temporaryLinks[file.uuid].url" readonly class="demo-link-input" />
                                <a :href="temporaryLinks[file.uuid].url" target="_blank" rel="noopener noreferrer">Abrir descarga temporal</a>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card">
                <div class="mb-4">
                    <h3 class="m-0">Historial de descargas</h3>
                    <p class="m-0 text-sm text-color-secondary">Registro del usuario actual dentro de la organizacion activa. Sirve como base para la futura seccion `Downloads`.</p>
                </div>

                <div v-if="state.downloads.length === 0" class="demo-empty-state">Aun no hay descargas registradas en esta sesion y organizacion.</div>

                <div v-else class="demo-download-list">
                    <article v-for="download in state.downloads" :key="download.id" class="demo-download-card">
                        <div class="font-semibold">{{ download.file.original_name }}</div>
                        <div class="text-sm text-color-secondary">{{ download.channel }} · {{ download.status }} · {{ formatDate(download.downloaded_at) }}</div>
                        <div class="text-sm text-color-secondary">
                            {{ formatBytes(download.file.size_bytes) }}
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.demo-summary {
    display: flex;
    gap: 1rem;
}

.demo-summary > div {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 6rem;
    padding: 0.9rem 1rem;
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--surface-card), var(--surface-ground));
}

.demo-summary strong {
    font-size: 1.35rem;
}

.demo-summary span {
    font-size: 0.85rem;
    color: var(--text-color-secondary);
}

.demo-textarea,
.demo-link-input,
input[type='file'] {
    width: 100%;
}

.demo-textarea,
.demo-link-input {
    border: 1px solid var(--surface-border);
    border-radius: 0.85rem;
    padding: 0.85rem 1rem;
    background: var(--surface-card);
    color: var(--text-color);
}

.demo-primary-button,
.demo-secondary-button {
    border: 0;
    border-radius: 999px;
    padding: 0.75rem 1rem;
    font-weight: 600;
    cursor: pointer;
}

.demo-primary-button {
    background: var(--primary-color);
    color: var(--primary-contrast-color);
}

.demo-secondary-button {
    background: var(--surface-200);
    color: var(--text-color);
}

.demo-primary-button:disabled,
.demo-secondary-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.demo-empty-state {
    border: 1px dashed var(--surface-border);
    border-radius: 1rem;
    padding: 1rem;
    color: var(--text-color-secondary);
    background: var(--surface-ground);
}

.demo-file-list,
.demo-download-list {
    display: grid;
    gap: 1rem;
}

.demo-file-card,
.demo-download-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    padding: 1rem;
    background: var(--surface-card);
}

.demo-token-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
}

.demo-token-row code {
    padding: 0.25rem 0.5rem;
    border-radius: 0.5rem;
    background: var(--surface-ground);
}

.demo-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.demo-link-box {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.9rem;
    border-radius: 0.85rem;
    background: var(--surface-ground);
}

@media (max-width: 768px) {
    .demo-summary {
        width: 100%;
    }

    .demo-summary > div {
        flex: 1;
    }
}
</style>
