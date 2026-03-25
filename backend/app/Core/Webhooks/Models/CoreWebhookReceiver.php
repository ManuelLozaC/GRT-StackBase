<?php

namespace App\Core\Webhooks\Models;

use App\Models\Organizacion;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoreWebhookReceiver extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_webhook_receivers';

    protected $fillable = [
        'organizacion_id',
        'module_key',
        'event_key',
        'source_name',
        'signing_secret',
        'is_active',
        'last_received_at',
    ];

    protected $hidden = [
        'signing_secret',
    ];

    protected function casts(): array
    {
        return [
            'signing_secret' => 'encrypted',
            'is_active' => 'boolean',
            'last_received_at' => 'datetime',
        ];
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(CoreWebhookReceipt::class, 'receiver_id');
    }
}
