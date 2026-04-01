<?php

namespace App\Core\Errors\Models;

use App\Models\Organizacion;
use App\Models\User;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreErrorLog extends Model
{
    use HasFactory, MultiTenantable;

    protected $table = 'core_error_logs';

    protected $fillable = [
        'organizacion_id',
        'actor_id',
        'request_id',
        'ip_address',
        'error_class',
        'error_code',
        'message',
        'file_path',
        'line_number',
        'context',
        'trace_excerpt',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'trace_excerpt' => 'array',
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

    public function empresa(): BelongsTo
    {
        return $this->organizacion();
    }

    public function company(): BelongsTo
    {
        return $this->organizacion();
    }
}
