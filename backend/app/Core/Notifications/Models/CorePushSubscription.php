<?php

namespace App\Core\Notifications\Models;

use App\Models\Organizacion;
use App\Models\User;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorePushSubscription extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_push_subscriptions';

    protected $fillable = [
        'organizacion_id',
        'user_id',
        'token',
        'device_name',
        'platform',
        'browser',
        'endpoint',
        'is_active',
        'last_used_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
