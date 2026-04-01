<?php

namespace App\Core\Security\Models;

use App\Models\Organizacion;
use App\Models\User;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreSecurityLog extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_security_logs';

    protected $fillable = [
        'organizacion_id',
        'actor_id',
        'event_key',
        'severity',
        'ip_address',
        'request_id',
        'summary',
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
}
