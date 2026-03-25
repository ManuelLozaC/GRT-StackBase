<?php

namespace App\Models;

use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Area extends Model
{
    use HasFactory, MultiTenantable, SoftDeletes;

    protected $table = 'areas';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'division_id',
        'nombre',
        'slug',
        'descripcion',
        'activa',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $area): void {
            if (! $area->uuid) {
                $area->uuid = (string) Str::uuid();
            }
        });
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function asignacionesLaborales(): HasMany
    {
        return $this->hasMany(AsignacionLaboral::class);
    }
}
