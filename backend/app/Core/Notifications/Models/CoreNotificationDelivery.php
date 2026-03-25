<?php

namespace App\Core\Notifications\Models;

use App\Models\Organizacion;
use App\Models\User;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreNotificationDelivery extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_notification_deliveries';

    protected $fillable = [
        'organizacion_id',
        'notification_id',
        'recipient_id',
        'channel',
        'status',
        'destination',
        'status_detail',
        'metadata',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(CoreNotification::class, 'notification_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
