<?php

namespace App\Http\Controllers\Api\V1\Demo;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Files\Models\FileDownload;
use App\Core\Files\Models\ManagedFile;
use App\Core\Files\Services\FileManager;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Jobs\Models\CoreJobRun;
use App\Core\Metrics\MetricsRecorder;
use App\Core\Modules\ModuleSettingsManager;
use App\Core\Webhooks\WebhookManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Demo\CreateAsyncFilePackageRequest;
use App\Http\Requests\Api\V1\Demo\CreateTemporaryFileLinkRequest;
use App\Http\Requests\Api\V1\Demo\StoreDemoFileRequest;
use App\Http\Requests\Api\V1\Demo\StoreDemoFileVersionRequest;
use App\Jobs\Demo\ProcessDemoFilePackageRun;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class DemoFileController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FileManager $fileManager,
        protected AuditLogger $auditLogger,
        protected ModuleSettingsManager $moduleSettings,
        protected MetricsRecorder $metrics,
        protected WebhookManager $webhooks,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $files = ManagedFile::query()
            ->with(['uploader:id,name'])
            ->whereNull('superseded_at')
            ->latest('id')
            ->get();

        return $this->successResponse(
            data: $files->map(fn (ManagedFile $file): array => $this->transformFile($file))->all(),
            message: 'Archivos de demo listados',
            meta: [
                'total' => $files->count(),
            ],
        );
    }

    public function store(StoreDemoFileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $file = $this->fileManager->storeUploadedFile(
            uploadedFile: $request->file('file'),
            user: $user,
            metadata: [
                'source' => 'demo-platform',
                'notes' => $request->string('notes')->toString() ?: null,
            ],
            attachment: [
                'resource_key' => $request->string('attached_resource_key')->toString() ?: null,
                'record_id' => $request->integer('attached_record_id') ?: null,
                'record_label' => $request->string('attached_record_label')->toString() ?: null,
            ],
        );

        $this->auditLogger->record(
            eventKey: 'demo.file.uploaded',
            actor: $user,
            entityType: 'core_file',
            entityKey: $file->uuid,
            summary: 'Se cargo un archivo de demo',
            sourceModule: 'demo-platform',
            context: [
                'original_name' => $file->original_name,
                'size_bytes' => $file->size_bytes,
            ],
        );
        $this->metrics->record(
            moduleKey: 'demo-platform',
            eventKey: 'demo.file.uploaded',
            eventCategory: 'files',
            actor: $user,
            context: [
                'file_uuid' => $file->uuid,
            ],
        );
        $this->webhooks->dispatch(
            moduleKey: 'demo-platform',
            eventKey: 'demo.file.uploaded',
            payload: [
                'file' => [
                    'uuid' => $file->uuid,
                    'original_name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size_bytes' => $file->size_bytes,
                    'version' => $file->version,
                ],
                'uploaded_by' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
                'occurred_at' => now()->toIso8601String(),
            ],
            actor: $user,
        );

        return $this->successResponse(
            data: $this->transformFile($file->load('uploader:id,name')),
            message: 'Archivo cargado correctamente',
        );
    }

    public function storeVersion(StoreDemoFileVersionRequest $request, ManagedFile $file): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $newVersion = $this->fileManager->createNewVersion(
            currentFile: $file,
            uploadedFile: $request->file('file'),
            user: $user,
            metadata: [
                'source' => 'demo-platform',
                'notes' => $request->string('notes')->toString() ?: null,
            ],
        );

        $this->auditLogger->record(
            eventKey: 'demo.file.version_uploaded',
            actor: $user,
            entityType: 'core_file',
            entityKey: $newVersion->uuid,
            summary: 'Se cargo una nueva version de archivo de demo',
            sourceModule: 'demo-platform',
            context: [
                'version_group_uuid' => $newVersion->version_group_uuid,
                'version' => $newVersion->version,
                'previous_version_id' => $file->id,
            ],
        );
        $this->metrics->record(
            moduleKey: 'demo-platform',
            eventKey: 'demo.file.version_uploaded',
            eventCategory: 'files',
            actor: $user,
            context: [
                'file_uuid' => $newVersion->uuid,
                'version_group_uuid' => $newVersion->version_group_uuid,
                'version' => $newVersion->version,
            ],
        );

        return $this->successResponse(
            data: $this->transformFile($newVersion->load('uploader:id,name')),
            message: 'Nueva version cargada correctamente',
        );
    }

    public function versions(Request $request, ManagedFile $file): JsonResponse
    {
        $versions = $file->versionHistory()
            ->loadMissing('uploader:id,name');

        return $this->successResponse(
            data: $versions->map(fn (ManagedFile $version): array => $this->transformFile($version))->all(),
            message: 'Historial de versiones listado',
            meta: [
                'total' => $versions->count(),
            ],
        );
    }

    public function download(Request $request, ManagedFile $file): StreamedResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        $this->fileManager->recordDownload(
            file: $file,
            user: $user,
            channel: 'direct',
            metadata: [
                'source' => 'demo-platform',
            ],
        );

        $this->auditLogger->record(
            eventKey: 'demo.file.downloaded',
            actor: $user,
            entityType: 'core_file',
            entityKey: $file->uuid,
            summary: 'Se descargo un archivo de demo',
            sourceModule: 'demo-platform',
            context: [
                'channel' => 'direct',
                'original_name' => $file->original_name,
            ],
        );
        $this->metrics->record(
            moduleKey: 'demo-platform',
            eventKey: 'demo.file.downloaded',
            eventCategory: 'files',
            actor: $user,
            context: [
                'file_uuid' => $file->uuid,
                'channel' => 'direct',
            ],
        );

        return $this->fileManager->downloadResponse($file);
    }

    public function temporaryLink(CreateTemporaryFileLinkRequest $request, ManagedFile $file): JsonResponse
    {
        $ttlMinutes = $request->integer(
            'ttl_minutes',
            (int) $this->moduleSettings->get('demo-platform', 'default_file_ttl_minutes', 30),
        );

        $payload = $this->fileManager->createTemporaryDownloadUrl(
            file: $file,
            ttlMinutes: $ttlMinutes,
        );

        $this->auditLogger->record(
            eventKey: 'demo.file.temporary_link.generated',
            actor: $request->user(),
            entityType: 'core_file',
            entityKey: $file->uuid,
            summary: 'Se genero un link temporal de archivo',
            sourceModule: 'demo-platform',
            context: [
                'ttl_minutes' => $ttlMinutes,
            ],
        );
        $this->metrics->record(
            moduleKey: 'demo-platform',
            eventKey: 'demo.file.temporary_link.generated',
            eventCategory: 'files',
            actor: $request->user(),
            context: [
                'file_uuid' => $file->uuid,
            ],
        );

        return $this->successResponse(
            data: $payload,
            message: 'Link temporal generado',
        );
    }

    public function temporaryDownload(Request $request, ManagedFile $file): StreamedResponse
    {
        $this->fileManager->recordDownload(
            file: $file,
            user: $request->user(),
            channel: 'signed-url',
            metadata: [
                'source' => 'demo-platform',
            ],
        );

        $this->auditLogger->record(
            eventKey: 'demo.file.downloaded',
            actor: $request->user(),
            entityType: 'core_file',
            entityKey: $file->uuid,
            summary: 'Se descargo un archivo de demo mediante signed URL',
            sourceModule: 'demo-platform',
            context: [
                'channel' => 'signed-url',
                'original_name' => $file->original_name,
            ],
        );

        return $this->fileManager->downloadResponse($file);
    }

    public function downloads(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $downloads = FileDownload::query()
            ->with(['file:id,uuid,original_name,size_bytes'])
            ->where('user_id', $user->id)
            ->latest('downloaded_at')
            ->get();

        return $this->successResponse(
            data: $downloads->map(fn (FileDownload $download): array => $this->transformDownload($download))->all(),
            message: 'Historial de descargas listado',
            meta: [
                'total' => $downloads->count(),
            ],
        );
    }

    public function packages(Request $request): JsonResponse
    {
        $runs = CoreJobRun::query()
            ->with('requester:id,name')
            ->where('job_key', 'demo.files.package')
            ->latest('id')
            ->get();

        return $this->successResponse(
            data: $runs->map(fn (CoreJobRun $run): array => $this->transformPackageRun($run))->all(),
            message: 'Paquetes async listados',
            meta: [
                'total' => $runs->count(),
            ],
        );
    }

    public function queuePackage(CreateAsyncFilePackageRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $run = $this->fileManager->queuePackage(
            fileUuids: $request->collect('file_uuids')
                ->map(fn (mixed $value): string => (string) $value)
                ->all(),
            user: $user,
        );

        ProcessDemoFilePackageRun::dispatch($run->id);

        $this->auditLogger->record(
            eventKey: 'demo.file.package_queued',
            actor: $user,
            entityType: 'core_job_run',
            entityKey: $run->uuid,
            summary: 'Se solicito un paquete async de archivos',
            sourceModule: 'demo-platform',
            context: [
                'file_count' => $run->requested_payload['file_count'] ?? 0,
            ],
        );
        $this->metrics->record(
            moduleKey: 'demo-platform',
            eventKey: 'demo.file.package_queued',
            eventCategory: 'files',
            actor: $user,
            context: [
                'job_uuid' => $run->uuid,
                'file_count' => $run->requested_payload['file_count'] ?? 0,
            ],
        );

        return $this->successResponse(
            data: $this->transformPackageRun($run->fresh()->load('requester:id,name')),
            message: 'Paquete async enviado a cola',
            meta: [
                'worker_hint' => 'Ejecuta php artisan queue:work --queue=files para preparar paquetes pendientes.',
            ],
        );
    }

    public function retryPackage(Request $request, CoreJobRun $jobRun): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($jobRun->job_key !== 'demo.files.package') {
            return $this->errorResponse(
                message: 'El job solicitado no corresponde a un paquete de archivos.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (! in_array($jobRun->status, ['failed', 'pending'], true)) {
            return $this->errorResponse(
                message: 'Solo se pueden reintentar paquetes fallidos o pendientes.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $jobRun->forceFill([
            'status' => 'pending',
            'started_at' => null,
            'finished_at' => null,
            'failed_at' => null,
            'error_message' => null,
            'result_payload' => null,
            'dispatched_at' => now(),
        ])->save();

        ProcessDemoFilePackageRun::dispatch($jobRun->id);

        $this->auditLogger->record(
            eventKey: 'demo.file.package_retried',
            actor: $user,
            entityType: 'core_job_run',
            entityKey: $jobRun->uuid,
            summary: 'Se reintento un paquete async de archivos',
            sourceModule: 'demo-platform',
            context: [
                'job_uuid' => $jobRun->uuid,
            ],
        );

        return $this->successResponse(
            data: $this->transformPackageRun($jobRun->fresh()->load('requester:id,name')),
            message: 'Paquete async reenviado a cola',
            meta: [
                'worker_hint' => 'Ejecuta php artisan queue:work --queue=files para preparar paquetes pendientes.',
            ],
        );
    }

    public function downloadPackage(Request $request, CoreJobRun $jobRun): StreamedResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        $response = $this->fileManager->downloadPreparedPackage($jobRun);

        $this->auditLogger->record(
            eventKey: 'demo.file.package_downloaded',
            actor: $user,
            entityType: 'core_job_run',
            entityKey: $jobRun->uuid,
            summary: 'Se descargo un paquete async de archivos',
            sourceModule: 'demo-platform',
            context: [
                'artifact_name' => $jobRun->result_payload['artifact_name'] ?? null,
                'file_count' => $jobRun->result_payload['file_count'] ?? null,
            ],
        );

        return $response;
    }

    protected function transformFile(ManagedFile $file): array
    {
        return [
            'id' => $file->id,
            'uuid' => $file->uuid,
            'original_name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'size_bytes' => $file->size_bytes,
            'version' => $file->version,
            'security_token' => $file->security_token,
            'version_group_uuid' => $file->version_group_uuid,
            'previous_version_uuid' => $file->previousVersion?->uuid,
            'superseded_at' => $file->superseded_at?->toIso8601String(),
            'uploaded_at' => $file->created_at?->toIso8601String(),
            'uploaded_by' => $file->uploader?->name,
            'attachment' => [
                'resource_key' => $file->attached_resource_key,
                'record_id' => $file->attached_record_id,
                'record_label' => $file->attached_record_label,
            ],
        ];
    }

    protected function transformDownload(FileDownload $download): array
    {
        return [
            'id' => $download->id,
            'channel' => $download->channel,
            'status' => $download->status,
            'downloaded_at' => $download->downloaded_at?->toIso8601String(),
            'file' => [
                'uuid' => $download->file?->uuid,
                'original_name' => $download->file?->original_name,
                'size_bytes' => $download->file?->size_bytes,
            ],
        ];
    }

    protected function transformPackageRun(CoreJobRun $run): array
    {
        $result = $run->result_payload ?? [];
        $requested = $run->requested_payload ?? [];

        return [
            'uuid' => $run->uuid,
            'status' => $run->status,
            'job_key' => $run->job_key,
            'queue' => $run->queue,
            'attempts' => $run->attempts,
            'max_tries' => 3,
            'backoff_schedule' => [10, 30],
            'requested_by' => $run->requester?->name,
            'file_count' => $result['file_count'] ?? $requested['file_count'] ?? 0,
            'requested_files' => $requested['original_names'] ?? [],
            'artifact_name' => $result['artifact_name'] ?? null,
            'download_url' => $result['download_url'] ?? null,
            'error_message' => $run->error_message,
            'can_retry' => in_array($run->status, ['failed', 'pending'], true),
            'dispatched_at' => $run->dispatched_at?->toIso8601String(),
            'started_at' => $run->started_at?->toIso8601String(),
            'finished_at' => $run->finished_at?->toIso8601String(),
        ];
    }
}
