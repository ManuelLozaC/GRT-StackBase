<?php

namespace App\Core\Files\Services;

class StorageDiskResolver
{
    public function forManagedFiles(): string
    {
        $preferred = (string) config('filesystems.default', 'local');

        return $this->resolveDisk($preferred);
    }

    public function forDataExports(): string
    {
        $preferred = (string) config('filesystems.data_exports_disk', config('filesystems.default', 'local'));

        return $this->resolveDisk($preferred);
    }

    protected function resolveDisk(string $preferred): string
    {
        if ($preferred !== 'spaces') {
            return $preferred;
        }

        return $this->spacesIsConfigured()
            ? 'spaces'
            : (string) config('filesystems.fallback_disk', 'local');
    }

    protected function spacesIsConfigured(): bool
    {
        $required = [
            config('filesystems.disks.spaces.key'),
            config('filesystems.disks.spaces.secret'),
            config('filesystems.disks.spaces.bucket'),
            config('filesystems.disks.spaces.endpoint'),
        ];

        return collect($required)
            ->every(fn (mixed $value): bool => is_string($value) && trim($value) !== '');
    }
}
