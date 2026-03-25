<?php

namespace App\Http\Controllers\Api\V1\Demo;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Files\Models\FileDownload;
use App\Core\Files\Models\ManagedFile;
use App\Core\Files\Services\FileManager;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Metrics\MetricsRecorder;
use App\Core\Modules\ModuleSettingsManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Demo\CreateTemporaryFileLinkRequest;
use App\Http\Requests\Api\V1\Demo\StoreDemoFileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DemoFileController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FileManager $fileManager,
        protected AuditLogger $auditLogger,
        protected ModuleSettingsManager $moduleSettings,
        protected MetricsRecorder $metrics,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $files = ManagedFile::query()
            ->with(['uploader:id,name'])
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

        return $this->successResponse(
            data: $this->transformFile($file->load('uploader:id,name')),
            message: 'Archivo cargado correctamente',
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
            'uploaded_at' => $file->created_at?->toIso8601String(),
            'uploaded_by' => $file->uploader?->name,
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
}
