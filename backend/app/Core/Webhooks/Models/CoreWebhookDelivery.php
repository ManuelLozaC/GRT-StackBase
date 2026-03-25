<?php

namespace App\Core\Webhooks\Models;

use App\Models\Organizacion;
use App\Models\User;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreWebhookDelivery extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_webhook_deliveries';

    protected $fillable = [
        'organizacion_id',
        'endpoint_id',
        'actor_id',
        'module_key',
        'event_key',
        'target_url',
        'request_headers',
        'request_body',
        'status',
        'response_status',
        'response_body',
        'error_message',
        'request_id',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'request_body' => 'array',
            'delivered_at' => 'datetime',
        ];
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(CoreWebhookEndpoint::class, 'endpoint_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
