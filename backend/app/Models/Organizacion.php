<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organizacion extends Model
{
    use HasFactory;

    protected $table = 'organizaciones';

    protected $fillable = [
        'nombre',
        'slug',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $organizacion): void {
            $empresa = Empresa::withoutGlobalScopes()
                ->withTrashed()
                ->where('organizacion_id', $organizacion->id)
                ->where('slug', $organizacion->slug)
                ->first();

            if ($empresa === null) {
                $empresa = new Empresa([
                    'organizacion_id' => $organizacion->id,
                    'slug' => $organizacion->slug,
                ]);
            }

            if ($empresa->trashed()) {
                $empresa->restore();
            }

            $empresa->fill([
                'nombre' => $organizacion->nombre,
                'metadata' => array_merge(
                    $organizacion->metadata ?? [],
                    [
                        'kind' => 'organization-mirror',
                        'same_as_organizacion' => true,
                    ],
                ),
            ]);
            $empresa->save();
        });

        static::deleting(function (self $organizacion): void {
            Empresa::withoutGlobalScopes()
                ->withTrashed()
                ->where('organizacion_id', $organizacion->id)
                ->where('slug', $organizacion->slug)
                ->get()
                ->each
                ->delete();
        });
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    public function usuariosActivos(): HasMany
    {
        return $this->hasMany(User::class, 'organizacion_activa_id');
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class);
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    public function oficinas(): HasMany
    {
        return $this->hasMany(Oficina::class);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class);
    }

    public function divisiones(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class);
    }

    public function asignacionesLaborales(): HasMany
    {
        return $this->hasMany(AsignacionLaboral::class);
    }
}
