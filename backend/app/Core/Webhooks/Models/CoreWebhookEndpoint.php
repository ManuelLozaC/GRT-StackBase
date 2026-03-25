<?php

namespace App\Core\Webhooks\Models;

use App\Models\Organizacion;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoreWebhookEndpoint extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_webhook_endpoints';

    protected $fillable = [
        'organizacion_id',
        'module_key',
        'event_key',
        'target_url',
        'signing_secret',
        'custom_headers',
        'is_active',
        'last_delivered_at',
    ];

    protected $hidden = [
        'signing_secret',
    ];

    protected function casts(): array
    {
        return [
            'signing_secret' => 'encrypted',
            'custom_headers' => 'array',
            'is_active' => 'boolean',
            'last_delivered_at' => 'datetime',
        ];
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(CoreWebhookDelivery::class, 'endpoint_id');
    }
}
