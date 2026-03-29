<script setup>
import DemoPatternGuide from '@/components/demo/DemoPatternGuide.vue';
import api from '@/service/api';
import { onMounted, reactive, ref } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const state = reactive({
    loading: false,
    uploading: false,
    versionUploadingUuid: null,
    downloadingUuid: null,
    packageDownloadingUuid: null,
    generatingUuid: null,
    packaging: false,
    packageRetryingUuid: null,
    files: [],
    downloads: [],
    packages: [],
    versions: {}
});

const selectedFile = ref(null);
const notes = ref('');
const attachedResourceKey = ref('people');
const attachedRecordId = ref('');
const attachedRecordLabel = ref('');
const temporaryLinks = ref({});
const selectedFileUuids = ref([]);

async function loadData() {
    state.loading = true;

    try {
        const [filesResponse, downloadsResponse, packagesResponse] = await Promise.all([api.get('/v1/demo/files'), api.get('/v1/demo/files/downloads'), api.get('/v1/demo/files/packages')]);

        state.files = filesResponse.data.datos ?? [];
        state.downloads = downloadsResponse.data.datos ?? [];
        state.packages = packagesResponse.data.datos ?? [];
        selectedFileUuids.value = selectedFileUuids.value.filter((uuid) => state.files.some((file) => file.uuid === uuid));
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
        formData.append('attached_resource_key', attachedResourceKey.value);
        formData.append('attached_record_id', attachedRecordId.value);
        formData.append('attached_record_label', attachedRecordLabel.value);

        await api.post('/v1/demo/files', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        selectedFile.value = null;
        notes.value = '';
        attachedResourceKey.value = 'people';
        attachedRecordId.value = '';
        attachedRecordLabel.value = '';

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

async function uploadNewVersion(file) {
    const input = document.getElementById(`demo-file-version-${file.uuid}`);
    const versionFile = input?.files?.[0];

    if (!versionFile) {
        toast.add({
            severity: 'warn',
            summary: 'Archivo requerido',
            detail: 'Selecciona el archivo de reemplazo antes de crear una nueva version.',
            life: 2500
        });

        return;
    }

    state.versionUploadingUuid = file.uuid;

    try {
        const formData = new FormData();
        formData.append('file', versionFile);
        formData.append('notes', `Nueva version para ${file.original_name}`);

        await api.post(`/v1/demo/files/${file.uuid}/versions`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        input.value = '';

        toast.add({
            severity: 'success',
            summary: 'Nueva version cargada',
            detail: 'El archivo anterior quedo historizado y la nueva version ya esta activa.',
            life: 2800
        });

        await Promise.all([loadData(), loadVersions(file)]);
    } finally {
        state.versionUploadingUuid = null;
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

async function loadVersions(file) {
    const response = await api.get(`/v1/demo/files/${file.uuid}/versions`);
    state.versions[file.uuid] = response.data.datos ?? [];
}

async function queueAsyncPackage() {
    if (selectedFileUuids.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'Selecciona archivos',
            detail: 'Marca al menos un archivo antes de preparar un paquete async.',
            life: 2500
        });

        return;
    }

    state.packaging = true;

    try {
        await api.post('/v1/demo/files/packages', {
            file_uuids: selectedFileUuids.value
        });

        toast.add({
            severity: 'success',
            summary: 'Paquete enviado a cola',
            detail: 'La preparacion pesada quedo en segundo plano y aparecera en el historial de paquetes.',
            life: 3000
        });

        await loadData();
    } finally {
        state.packaging = false;
    }
}

async function retryPackage(pkg) {
    state.packageRetryingUuid = pkg.uuid;

    try {
        await api.post(`/v1/demo/files/packages/${pkg.uuid}/retry`);

        toast.add({
            severity: 'success',
            summary: 'Paquete reenviado',
            detail: 'El paquete async volvio a cola para reintentarse.',
            life: 2800
        });

        await loadData();
    } finally {
        state.packageRetryingUuid = null;
    }
}

async function downloadPackage(pkg) {
    state.packageDownloadingUuid = pkg.uuid;

    try {
        const response = await api.get(`/v1/demo/files/packages/${pkg.uuid}/download`, {
            responseType: 'blob'
        });

        const url = window.URL.createObjectURL(response.data);
        const link = document.createElement('a');
        link.href = url;
        link.download = pkg.artifact_name || 'demo-files-package.zip';
        link.click();
        window.URL.revokeObjectURL(url);
    } finally {
        state.packageDownloadingUuid = null;
    }
}

function toggleSelectAllFiles() {
    if (selectedFileUuids.value.length === state.files.length) {
        selectedFileUuids.value = [];
        return;
    }

    selectedFileUuids.value = state.files.map((file) => file.uuid);
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
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="m-0">Demo funcional de archivos</h2>
                        <p class="m-0 text-color-secondary">Esta demo valida carga, almacenamiento, descarga directa, signed URLs, historial y asociacion base a entidades de negocio dentro del tenant activo.</p>
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
                <input v-model="attachedResourceKey" type="text" class="demo-link-input" placeholder="Recurso asociado, por ejemplo: people" />
                <input v-model="attachedRecordId" type="number" min="1" class="demo-link-input" placeholder="ID del registro asociado" />
                <input v-model="attachedRecordLabel" type="text" class="demo-link-input" placeholder="Etiqueta opcional del registro asociado" />

                <button class="demo-primary-button" :disabled="state.uploading" @click="uploadFile">
                    {{ state.uploading ? 'Subiendo...' : 'Subir archivo demo' }}
                </button>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="card">
                <div class="app-panel-header">
                    <div class="app-panel-header-copy">
                        <h3 class="m-0">Archivos disponibles</h3>
                        <p class="m-0 text-sm text-color-secondary">Listado del tenant activo con version, metadatos y asociacion opcional a un recurso real.</p>
                    </div>
                    <div class="app-panel-actions">
                        <button class="demo-secondary-button app-button-standard" :disabled="state.packaging || state.files.length === 0" @click="toggleSelectAllFiles">
                            {{ selectedFileUuids.length === state.files.length && state.files.length > 0 ? 'Quitar seleccion' : 'Seleccionar visibles' }}
                        </button>
                        <button class="demo-primary-button app-button-standard" :disabled="state.packaging || selectedFileUuids.length === 0" @click="queueAsyncPackage">
                            {{ state.packaging ? 'Encolando paquete...' : `Preparar paquete async (${selectedFileUuids.length})` }}
                        </button>
                        <button class="demo-secondary-button app-button-standard" :disabled="state.loading" @click="loadData">
                            {{ state.loading ? 'Actualizando...' : 'Actualizar' }}
                        </button>
                    </div>
                </div>

                <div v-if="state.files.length === 0" class="demo-empty-state">Todavia no hay archivos en esta empresa. Sube uno para probar el flujo completo.</div>

                <div v-else class="demo-file-list">
                    <article v-for="file in state.files" :key="file.uuid" class="demo-file-card">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <input v-model="selectedFileUuids" type="checkbox" :value="file.uuid" class="mt-1" />
                                    <div>
                                        <div class="font-semibold">{{ file.original_name }}</div>
                                        <div class="text-sm text-color-secondary">{{ formatBytes(file.size_bytes) }} | {{ file.mime_type || 'tipo no detectado' }}</div>
                                    </div>
                                </div>
                                <Tag severity="contrast" :value="`v${file.version}`" />
                            </div>

                            <div class="text-sm text-color-secondary">Subido por {{ file.uploaded_by || 'sistema' }} | {{ formatDate(file.uploaded_at) }}</div>
                            <div v-if="file.attachment?.resource_key" class="text-sm text-color-secondary">Asociado a {{ file.attachment.record_label || `#${file.attachment.record_id}` }} en {{ file.attachment.resource_key }}</div>

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
                                <button class="demo-secondary-button" @click="loadVersions(file)">Ver versiones</button>
                            </div>

                            <div class="demo-version-box">
                                <input :id="`demo-file-version-${file.uuid}`" type="file" />
                                <button class="demo-secondary-button" :disabled="state.versionUploadingUuid === file.uuid" @click="uploadNewVersion(file)">
                                    {{ state.versionUploadingUuid === file.uuid ? 'Subiendo version...' : 'Subir nueva version' }}
                                </button>
                            </div>

                            <div v-if="temporaryLinks[file.uuid]" class="demo-link-box">
                                <div class="text-sm font-medium">Link temporal activo hasta {{ formatDate(temporaryLinks[file.uuid].expires_at) }}</div>
                                <input :value="temporaryLinks[file.uuid].url" readonly class="demo-link-input" />
                                <a :href="temporaryLinks[file.uuid].url" target="_blank" rel="noopener noreferrer">Abrir descarga temporal</a>
                            </div>

                            <div v-if="state.versions[file.uuid]?.length" class="demo-link-box">
                                <div class="text-sm font-medium">Historial de versiones</div>
                                <div class="demo-version-list">
                                    <div v-for="versionItem in state.versions[file.uuid]" :key="versionItem.uuid" class="demo-version-row">
                                        <span
                                            ><strong>v{{ versionItem.version }}</strong> · {{ formatDate(versionItem.uploaded_at) }}</span
                                        >
                                        <span>{{ versionItem.superseded_at ? 'Historica' : 'Activa' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <DemoPatternGuide
                title="Guia para files y adjuntos"
                :when-to-use="['cuando necesitas subir archivos asociados a una entidad real del sistema', 'cuando el usuario debe descargar directo o compartir un link temporal', 'cuando quieres dejar trazabilidad de descargas y versiones']"
                :avoid-when="['cuando el archivo no necesita historial ni asociacion a negocio', 'cuando un link temporal se usa como reemplazo de permisos de acceso', 'cuando se suben archivos sin contexto de tenant o actor']"
                :wiring="['guardar metadata y asociacion de negocio junto con el archivo', 'usar descarga directa para uso inmediato y signed URL para comparticion controlada', 'refrescar historial despues de cada accion importante']"
                :notes="['esta demo ya usa el core real y el storage configurado', 'cuando la descarga sea pesada o incluya varios archivos, moverla a paquete async en cola']"
            />
        </div>

        <div class="col-span-12">
            <div class="card">
                <div class="app-panel-header">
                    <div class="app-panel-header-copy">
                        <h3 class="m-0">Paquetes async preparados</h3>
                        <p class="m-0 text-sm text-color-secondary">Referencia para descargas pesadas: el usuario solicita un paquete y la cola deja el artefacto listo para descargar despues.</p>
                    </div>
                    <div class="app-panel-actions">
                        <button class="demo-secondary-button app-button-standard" :disabled="state.loading" @click="loadData">Refrescar paquetes</button>
                    </div>
                </div>

                <div v-if="state.packages.length === 0" class="demo-empty-state">Todavia no hay paquetes async solicitados en esta empresa.</div>

                <div v-else class="demo-download-list">
                    <article v-for="pkg in state.packages" :key="pkg.uuid" class="demo-download-card">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold">{{ pkg.artifact_name || `Paquete ${pkg.uuid.slice(0, 8)}` }}</div>
                                    <div class="text-sm text-color-secondary">{{ pkg.file_count }} archivos | solicitado por {{ pkg.requested_by || 'sistema' }}</div>
                                </div>
                                <Tag :severity="pkg.status === 'completed' ? 'success' : pkg.status === 'failed' ? 'danger' : 'warning'" :value="pkg.status" />
                            </div>
                            <div class="text-sm text-color-secondary">Intentos {{ pkg.attempts }}/{{ pkg.max_tries }} | backoff {{ pkg.backoff_schedule.join(', ') }}s</div>
                            <div class="text-sm text-color-secondary">Despachado {{ formatDate(pkg.dispatched_at) }} | Finalizado {{ formatDate(pkg.finished_at) }}</div>
                            <div v-if="pkg.error_message" class="text-sm text-red-500">{{ pkg.error_message }}</div>

                            <div class="demo-actions">
                                <button class="demo-primary-button" :disabled="pkg.status !== 'completed' || state.packageDownloadingUuid === pkg.uuid" @click="downloadPackage(pkg)">
                                    {{ state.packageDownloadingUuid === pkg.uuid ? 'Descargando paquete...' : 'Descargar paquete' }}
                                </button>
                                <button class="demo-secondary-button" :disabled="!pkg.can_retry || state.packageRetryingUuid === pkg.uuid" @click="retryPackage(pkg)">
                                    {{ state.packageRetryingUuid === pkg.uuid ? 'Reintentando...' : 'Reintentar' }}
                                </button>
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
                    <p class="m-0 text-sm text-color-secondary">Registro del usuario actual dentro de la empresa activa. Sirve como base para una futura seccion de descargas.</p>
                </div>

                <div v-if="state.downloads.length === 0" class="demo-empty-state">Aun no hay descargas registradas en esta sesion y empresa.</div>

                <div v-else class="demo-download-list">
                    <article v-for="download in state.downloads" :key="download.id" class="demo-download-card">
                        <div class="font-semibold">{{ download.file.original_name }}</div>
                        <div class="text-sm text-color-secondary">{{ download.channel }} | {{ download.status }} | {{ formatDate(download.downloaded_at) }}</div>
                        <div class="text-sm text-color-secondary">{{ formatBytes(download.file.size_bytes) }}</div>
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
    min-width: 6rem;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--surface-card), var(--surface-ground));
    padding: 0.9rem 1rem;
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
    background: var(--surface-card);
    color: var(--text-color);
    padding: 0.85rem 1rem;
}

.demo-primary-button,
.demo-secondary-button {
    border: 0;
    border-radius: 999px;
    cursor: pointer;
    font-weight: 600;
    padding: 0.75rem 1rem;
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
    cursor: not-allowed;
    opacity: 0.6;
}

.demo-empty-state {
    border: 1px dashed var(--surface-border);
    border-radius: 1rem;
    background: var(--surface-ground);
    color: var(--text-color-secondary);
    padding: 1rem;
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
    background: var(--surface-card);
    padding: 1rem;
}

.demo-token-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
}

.demo-token-row code {
    border-radius: 0.5rem;
    background: var(--surface-ground);
    padding: 0.25rem 0.5rem;
}

.demo-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.demo-link-box {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    border-radius: 0.85rem;
    background: var(--surface-ground);
    padding: 0.9rem;
}

.demo-version-box {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
}

.demo-version-list {
    display: grid;
    gap: 0.5rem;
}

.demo-version-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    font-size: 0.9rem;
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
