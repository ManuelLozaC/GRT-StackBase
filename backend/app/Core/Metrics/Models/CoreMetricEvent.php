<?php

namespace App\Core\Metrics\Models;

use App\Models\Organizacion;
use App\Models\User;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreMetricEvent extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_metric_events';

    protected $fillable = [
        'organizacion_id',
        'actor_id',
        'module_key',
        'event_key',
        'event_category',
        'request_id',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class, 'organizacion_id');
    }
}
