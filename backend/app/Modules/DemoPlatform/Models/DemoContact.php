<?php

namespace App\Modules\DemoPlatform\Models;

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Organizacion;
use App\Models\Sucursal;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DemoContact extends Model
{
    use HasFactory, MultiTenantable, SoftDeletes;

    protected $table = 'demo_contacts';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'nombre',
        'email',
        'telefono',
        'empresa',
        'empresa_id',
        'sucursal_id',
        'equipo_id',
        'estado',
        'prioridad',
        'notas',
        'metadata',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'custom_fields' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $contact): void {
            if (! $contact->uuid) {
                $contact->uuid = (string) Str::uuid();
            }
        });
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function empresaRelacion(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function sucursalRelacion(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function equipoRelacion(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }
}
