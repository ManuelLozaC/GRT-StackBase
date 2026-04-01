<?php

namespace App\Core\DataEngine\Models;

use App\Models\Organizacion;
use App\Models\User;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CoreDataTransferRun extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_data_transfer_runs';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'requested_by',
        'resource_key',
        'source_module',
        'type',
        'status',
        'file_name',
        'mime_type',
        'records_total',
        'records_processed',
        'records_failed',
        'error_summary',
        'metadata',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $run): void {
            if (! $run->uuid) {
                $run->uuid = (string) Str::uuid();
            }
        });
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

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
