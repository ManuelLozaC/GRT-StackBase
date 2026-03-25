<?php

namespace App\Models;

use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AsignacionLaboral extends Model
{
    use HasFactory, MultiTenantable, SoftDeletes;

    protected $table = 'asignaciones_laborales';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'persona_id',
        'user_id',
        'oficina_id',
        'division_id',
        'area_id',
        'cargo_id',
        'jefe_asignacion_id',
        'aprobador_asignacion_id',
        'es_principal',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $asignacion): void {
            if (! $asignacion->uuid) {
                $asignacion->uuid = (string) Str::uuid();
            }
        });
    }

    public function getEtiquetaContextoAttribute(): string
    {
        $this->loadMissing([
            'persona:id,nombres,apellido_paterno,apellido_materno',
            'oficina:id,nombre',
            'cargo:id,nombre',
        ]);

        $partes = array_filter([
            $this->persona?->nombre_completo,
            $this->cargo?->nombre,
            $this->oficina?->nombre,
        ]);

        return $partes === []
            ? sprintf('Asignacion #%s', $this->getKey())
            : implode(' | ', $partes);
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function oficina(): BelongsTo
    {
        return $this->belongsTo(Oficina::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function jefeAsignacion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'jefe_asignacion_id');
    }

    public function aprobadorAsignacion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'aprobador_asignacion_id');
    }

    public function subordinados(): HasMany
    {
        return $this->hasMany(self::class, 'jefe_asignacion_id');
    }
}
