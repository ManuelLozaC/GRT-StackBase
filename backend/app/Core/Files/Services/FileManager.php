<?php

namespace App\Core\Files\Services;

use App\Core\Files\Models\FileDownload;
use App\Core\Files\Models\ManagedFile;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileManager
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected StorageDiskResolver $storageDisks,
    ) {
    }

    public function storeUploadedFile(UploadedFile $uploadedFile, User $user, array $metadata = []): ManagedFile
    {
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
            'organizacion_id' => $this->tenantContext->organizationId($user),
            'uploaded_by' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'extension' => $extension ?: null,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size_bytes' => $uploadedFile->getSize(),
            'visibility' => 'private',
            'version' => 1,
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
}
