<?php

namespace App\Models;

use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sucursal extends Model
{
    use HasFactory, MultiTenantable, SoftDeletes;

    protected $table = 'sucursales';

    protected $fillable = [
        'organizacion_id',
        'empresa_id',
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

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
