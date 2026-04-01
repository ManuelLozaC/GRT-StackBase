<?php

namespace App\Core\Files\Models;

use App\Models\Organizacion;
use App\Models\User;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

class ManagedFile extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_files';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'version_group_uuid',
        'extension',
        'mime_type',
        'size_bytes',
        'visibility',
        'attached_resource_key',
        'attached_record_id',
        'attached_record_label',
        'previous_version_id',
        'version',
        'superseded_at',
        'security_token',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'superseded_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->organizacion();
    }

    public function company(): BelongsTo
    {
        return $this->organizacion();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(FileDownload::class, 'managed_file_id');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    public function nextVersions(): HasMany
    {
        return $this->hasMany(self::class, 'previous_version_id');
    }

    public function versionHistory(): Collection
    {
        return self::query()
            ->where('version_group_uuid', $this->version_group_uuid ?: $this->uuid)
            ->orderByDesc('version')
            ->get();
    }
}
