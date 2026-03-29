<?php

namespace App\Core\Files\Services;

use App\Core\Files\Models\FileDownload;
use App\Core\Files\Models\ManagedFile;
use App\Core\Jobs\Models\CoreJobRun;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ZipArchive;

class FileManager
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected StorageDiskResolver $storageDisks,
    ) {
    }

    public function storeUploadedFile(UploadedFile $uploadedFile, User $user, array $metadata = [], array $attachment = []): ManagedFile
    {
        return $this->storeManagedFile(
            uploadedFile: $uploadedFile,
            user: $user,
            metadata: $metadata,
            attachment: $attachment,
            versionGroupUuid: null,
            previousVersion: null,
            version: 1,
        );
    }

    public function createNewVersion(ManagedFile $currentFile, UploadedFile $uploadedFile, User $user, array $metadata = [], array $attachment = []): ManagedFile
    {
        $currentFile->loadMissing('uploader');
        $resolvedAttachment = [
            'resource_key' => $attachment['resource_key'] ?? $currentFile->attached_resource_key,
            'record_id' => $attachment['record_id'] ?? $currentFile->attached_record_id,
            'record_label' => $attachment['record_label'] ?? $currentFile->attached_record_label,
        ];

        $currentFile->forceFill([
            'superseded_at' => now(),
        ])->save();

        return $this->storeManagedFile(
            uploadedFile: $uploadedFile,
            user: $user,
            metadata: array_merge($currentFile->metadata ?? [], $metadata, [
                'previous_version_uuid' => $currentFile->uuid,
            ]),
            attachment: $resolvedAttachment,
            versionGroupUuid: $currentFile->version_group_uuid ?: $currentFile->uuid,
            previousVersion: $currentFile,
            version: $currentFile->version + 1,
        );
    }

    protected function storeManagedFile(
        UploadedFile $uploadedFile,
        User $user,
        array $metadata = [],
        array $attachment = [],
        ?string $versionGroupUuid = null,
        ?ManagedFile $previousVersion = null,
        int $version = 1,
    ): ManagedFile {
        $disk = $this->storageDisks->forManagedFiles();
        $uuid = (string) Str::uuid();
        $extension = $uploadedFile->getClientOriginalExtension();
        $filename = $extension !== '' ? $uuid.'.'.$extension : $uuid;
        $path = $uploadedFile->storeAs(
            path: 'stackbase-files/'.now()->format('Y/m'),
            name: $filename,
            options: [
                'disk' => $disk,
            ],
        );

        return ManagedFile::query()->create([
            'uuid' => $uuid,
            'version_group_uuid' => $versionGroupUuid ?: $uuid,
            'organizacion_id' => $this->tenantContext->organizationId($user),
            'uploaded_by' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'extension' => $extension ?: null,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size_bytes' => $uploadedFile->getSize(),
            'visibility' => 'private',
            'attached_resource_key' => $attachment['resource_key'] ?? null,
            'attached_record_id' => $attachment['record_id'] ?? null,
            'attached_record_label' => $attachment['record_label'] ?? null,
            'previous_version_id' => $previousVersion?->id,
            'version' => $version,
            'security_token' => Str::upper(Str::random(12)),
            'metadata' => $metadata,
        ]);
    }

    public function createTemporaryDownloadUrl(ManagedFile $file, int $ttlMinutes = 30): array
    {
        $expiresAt = now()->addMinutes($ttlMinutes);

        return [
            'url' => URL::temporarySignedRoute(
                name: 'api.v1.demo.files.temporary-download',
                expiration: $expiresAt,
                parameters: [
                    'file' => $file->getRouteKey(),
                ],
            ),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    public function recordDownload(ManagedFile $file, ?User $user, string $channel, array $metadata = []): void
    {
        FileDownload::query()->create([
            'managed_file_id' => $file->id,
            'organizacion_id' => $this->tenantContext->organizationId($user) ?? $file->organizacion_id,
            'user_id' => $user?->id,
            'channel' => $channel,
            'status' => 'completed',
            'downloaded_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    public function downloadResponse(ManagedFile $file): StreamedResponse
    {
        $disk = Storage::disk($file->disk);
        $stream = $disk->readStream($file->path);

        if ($stream === false) {
            throw new RuntimeException('No se pudo abrir el archivo solicitado para descarga.');
        }

        return Response::streamDownload(function () use ($stream): void {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $file->original_name, [
            'Content-Type' => $file->mime_type ?? 'application/octet-stream',
        ]);
    }

    public function queuePackage(array $fileUuids, User $user): CoreJobRun
    {
        $files = ManagedFile::query()
            ->whereNull('superseded_at')
            ->whereIn('uuid', $fileUuids)
            ->get(['id', 'uuid', 'original_name']);

        if ($files->isEmpty()) {
            throw new RuntimeException('No se encontraron archivos validos para preparar el paquete.');
        }

        return CoreJobRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'organizacion_id' => $this->tenantContext->organizationId($user),
            'requested_by' => $user->id,
            'job_key' => 'demo.files.package',
            'queue' => 'files',
            'status' => 'pending',
            'requested_payload' => [
                'file_uuids' => $files->pluck('uuid')->all(),
                'file_count' => $files->count(),
                'original_names' => $files->pluck('original_name')->all(),
            ],
            'attempts' => 0,
            'dispatched_at' => now(),
        ]);
    }

    public function processQueuedPackage(CoreJobRun $jobRun, int $attempts = 1): CoreJobRun
    {
        if ($jobRun->status === 'completed') {
            return $jobRun;
        }

        $jobRun->forceFill([
            'status' => 'processing',
            'attempts' => $attempts,
            'started_at' => now(),
            'failed_at' => null,
            'finished_at' => null,
            'error_message' => null,
        ])->save();

        $fileUuids = collect($jobRun->requested_payload['file_uuids'] ?? [])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        $files = ManagedFile::query()
            ->whereIn('uuid', $fileUuids)
            ->orderBy('id')
            ->get();

        if ($files->isEmpty()) {
            throw new RuntimeException('El paquete solicitado ya no tiene archivos disponibles.');
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extension ZIP no esta disponible para generar el paquete.');
        }

        $artifact = $this->buildPackageArtifact($jobRun, $files);
        $storageDisk = $this->storageDisks->forDataExports();
        $storagePath = sprintf(
            'file-packages/%s/%s',
            $jobRun->organizacion_id ?? 'global',
            $artifact['file_name'],
        );

        Storage::disk($storageDisk)->put($storagePath, $artifact['content']);

        $jobRun->forceFill([
            'status' => 'completed',
            'result_payload' => [
                'artifact_name' => $artifact['file_name'],
                'artifact_mime_type' => 'application/zip',
                'storage_disk' => $storageDisk,
                'storage_path' => $storagePath,
                'download_url' => route('api.v1.demo.files.packages.download', ['jobRun' => $jobRun->uuid], absolute: false),
                'file_count' => $files->count(),
                'files' => $files->map(fn (ManagedFile $file): array => [
                    'uuid' => $file->uuid,
                    'original_name' => $file->original_name,
                    'version' => $file->version,
                    'size_bytes' => $file->size_bytes,
                ])->all(),
            ],
            'finished_at' => now(),
            'error_message' => null,
        ])->save();

        return $jobRun->fresh();
    }

    public function failQueuedPackage(CoreJobRun $jobRun, Throwable $exception, int $attempts = 1): void
    {
        $jobRun->forceFill([
            'status' => 'failed',
            'attempts' => $attempts,
            'failed_at' => now(),
            'finished_at' => now(),
            'error_message' => $exception->getMessage(),
        ])->save();
    }

    public function downloadPreparedPackage(CoreJobRun $jobRun): StreamedResponse
    {
        $result = $jobRun->result_payload ?? [];
        $storageDisk = $result['storage_disk'] ?? null;
        $storagePath = $result['storage_path'] ?? null;
        $artifactName = $result['artifact_name'] ?? 'demo-files-package.zip';

        if (! is_string($storageDisk) || ! is_string($storagePath) || ! Storage::disk($storageDisk)->exists($storagePath)) {
            throw new RuntimeException('El paquete preparado ya no esta disponible para descarga.');
        }

        return Storage::disk($storageDisk)->download(
            $storagePath,
            $artifactName,
            [
                'Content-Type' => 'application/zip',
            ],
        );
    }

    protected function buildPackageArtifact(CoreJobRun $jobRun, \Illuminate\Support\Collection $files): array
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'grt-file-package-');

        if ($temporaryFile === false) {
            throw new RuntimeException('No se pudo reservar un archivo temporal para el paquete.');
        }

        $zip = new ZipArchive();
        $opened = $zip->open($temporaryFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            @unlink($temporaryFile);
            throw new RuntimeException('No se pudo inicializar el paquete ZIP de descarga.');
        }

        $usedNames = [];

        foreach ($files as $index => $file) {
            $entryName = $this->uniqueArchiveEntryName($file->original_name, $usedNames, $index + 1);
            $stream = Storage::disk($file->disk)->readStream($file->path);

            if ($stream === false) {
                continue;
            }

            $contents = stream_get_contents($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($contents === false) {
                continue;
            }

            $zip->addFromString($entryName, $contents);
        }

        $zip->addFromString('manifest.json', json_encode([
            'generated_at' => now()->toIso8601String(),
            'requested_job_uuid' => $jobRun->uuid,
            'file_count' => $files->count(),
            'files' => $files->map(fn (ManagedFile $file): array => [
                'uuid' => $file->uuid,
                'original_name' => $file->original_name,
                'version' => $file->version,
                'size_bytes' => $file->size_bytes,
            ])->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');

        $zip->close();

        $content = file_get_contents($temporaryFile);
        @unlink($temporaryFile);

        if ($content === false) {
            throw new RuntimeException('No se pudo leer el paquete ZIP generado.');
        }

        return [
            'file_name' => sprintf('demo-files-package-%s.zip', now()->format('Ymd-His')),
            'content' => $content,
        ];
    }

    protected function uniqueArchiveEntryName(string $originalName, array &$usedNames, int $index): string
    {
        $clean = trim($originalName) !== '' ? $originalName : sprintf('archivo-%d.bin', $index);
        $clean = str_replace(['\\', '/'], '-', $clean);

        if (! in_array($clean, $usedNames, true)) {
            $usedNames[] = $clean;

            return $clean;
        }

        $extension = pathinfo($clean, PATHINFO_EXTENSION);
        $basename = pathinfo($clean, PATHINFO_FILENAME);
        $candidate = sprintf(
            '%s-v%d%s',
            $basename,
            $index,
            $extension !== '' ? '.'.$extension : '',
        );

        while (in_array($candidate, $usedNames, true)) {
            $index++;
            $candidate = sprintf(
                '%s-v%d%s',
                $basename,
                $index,
                $extension !== '' ? '.'.$extension : '',
            );
        }

        $usedNames[] = $candidate;

        return $candidate;
    }
}
