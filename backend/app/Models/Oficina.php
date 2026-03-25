<?php

declare(strict_types=1);

namespace App\Models;

class Oficina extends BaseModel
{
    protected $table = 'oficinas';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'ciudad_id',
        'nombre',
        'codigo',
        'direccion',
        'telefono',
        'es_principal',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'activa' => 'boolean',
        ];
    }
}
