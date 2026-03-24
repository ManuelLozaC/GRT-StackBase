<?php

declare(strict_types=1);

namespace App\Models;

class AsignacionLaboral extends BaseModel
{
    protected $table = 'asignaciones_laborales';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'persona_id',
        'usuario_id',
        'oficina_id',
        'division_id',
        'area_id',
        'cargo_id',
        'jefe_asignacion_laboral_id',
        'aprobador_asignacion_laboral_id',
        'es_principal',
        'activa',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'activa' => 'boolean',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }
}
