<?php

namespace App\Models;

use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Division extends Model
{
    use HasFactory, MultiTenantable, SoftDeletes;

    protected $table = 'divisiones';

    protected $fillable = [
        'uuid',
        'organizacion_id',
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
        static::creating(function (self $division): void {
            if (! $division->uuid) {
                $division->uuid = (string) Str::uuid();
            }
        });
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }
}
