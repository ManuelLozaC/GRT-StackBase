<?php

namespace App\Core\Webhooks\Models;

use App\Models\Organizacion;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreWebhookReceipt extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_webhook_receipts';

    protected $fillable = [
        'organizacion_id',
        'receiver_id',
        'module_key',
        'event_key',
        'source_name',
        'signature_status',
        'processing_status',
        'request_id',
        'ip_address',
        'request_headers',
        'request_body',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'request_body' => 'array',
            'received_at' => 'datetime',
        ];
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(CoreWebhookReceiver::class, 'receiver_id');
    }
}
