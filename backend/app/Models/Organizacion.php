<?php

declare(strict_types=1);

namespace App\Models;

class Organizacion extends BaseModel
{
    protected static bool $omitirAislamientoTenant = true;

    protected $table = 'organizaciones';

    protected $fillable = [
        'uuid',
        'nombre',
        'nombre_comercial',
        'nit',
        'email',
        'telefono',
        'direccion',
        'ciudad_id',
        'pais_id',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }
}
