<?php

declare(strict_types=1);

namespace App\Models;

class Cargo extends BaseModel
{
    protected $table = 'cargos';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'nombre',
        'descripcion',
        'es_aprobador',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'es_aprobador' => 'boolean',
            'activa' => 'boolean',
        ];
    }
}
