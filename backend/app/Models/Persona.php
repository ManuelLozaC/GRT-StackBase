<?php

namespace App\Models;

use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Persona extends Model
{
    use HasFactory, MultiTenantable, SoftDeletes;

    protected $table = 'personas';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'documento_identidad',
        'telefono',
        'correo',
        'direccion',
        'sexo',
        'fecha_nacimiento',
        'ciudad',
        'pais',
        'foto_path',
        'activa',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activa' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $persona): void {
            if (! $persona->uuid) {
                $persona->uuid = (string) Str::uuid();
            }
        });
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->nombres,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])));
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function asignacionesLaborales(): HasMany
    {
        return $this->hasMany(AsignacionLaboral::class);
    }
}
