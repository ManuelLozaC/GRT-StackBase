<?php

namespace App\Models;

use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Oficina extends Model
{
    use HasFactory, MultiTenantable, SoftDeletes;

    protected $table = 'oficinas';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'nombre',
        'slug',
        'codigo',
        'telefono',
        'direccion',
        'ciudad',
        'pais',
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
        static::creating(function (self $oficina): void {
            if (! $oficina->uuid) {
                $oficina->uuid = (string) Str::uuid();
            }
        });

        static::saved(function (self $oficina): void {
            $empresa = Empresa::withoutGlobalScopes()
                ->withTrashed()
                ->where('organizacion_id', $oficina->organizacion_id)
                ->orderBy('id')
                ->first();

            if ($empresa === null) {
                return;
            }

            $sucursal = Sucursal::withoutGlobalScopes()
                ->withTrashed()
                ->where('organizacion_id', $oficina->organizacion_id)
                ->where('slug', $oficina->slug)
                ->first();

            if ($sucursal === null) {
                $sucursal = new Sucursal([
                    'organizacion_id' => $oficina->organizacion_id,
                    'slug' => $oficina->slug,
                ]);
            }

            if ($sucursal->trashed()) {
                $sucursal->restore();
            }

            $sucursal->fill([
                'empresa_id' => $empresa->id,
                'nombre' => $oficina->nombre,
                'metadata' => array_merge(
                    $oficina->metadata ?? [],
                    [
                        'kind' => 'office-mirror',
                        'same_as_oficina' => true,
                        'oficina_id' => $oficina->id,
                    ],
                ),
            ]);
            $sucursal->save();
        });

        static::deleting(function (self $oficina): void {
            Sucursal::withoutGlobalScopes()
                ->withTrashed()
                ->where('organizacion_id', $oficina->organizacion_id)
                ->where('slug', $oficina->slug)
                ->get()
                ->each
                ->delete();
        });
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function asignacionesLaborales(): HasMany
    {
        return $this->hasMany(AsignacionLaboral::class);
    }
}
