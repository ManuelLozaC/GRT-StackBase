<?php

declare(strict_types=1);

namespace App\Models;

class Pais extends BaseModel
{
    protected static bool $omitirAislamientoTenant = true;

    protected $table = 'paises';

    protected $fillable = [
        'uuid',
        'nombre',
        'codigo_iso2',
        'codigo_iso3',
        'gentilicio',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
