<?php

namespace App\Core\Jobs\Models;

use App\Models\Organizacion;
use App\Models\User;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreJobRun extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_job_runs';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'requested_by',
        'job_key',
        'queue',
        'status',
        'requested_payload',
        'result_payload',
        'attempts',
        'dispatched_at',
        'started_at',
        'finished_at',
        'failed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'requested_payload' => 'array',
            'result_payload' => 'array',
            'dispatched_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'failed_at' => 'datetime',
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

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
