<?php

declare(strict_types=1);

namespace App\Models;

class Ciudad extends BaseModel
{
    protected static bool $omitirAislamientoTenant = true;

    protected $table = 'ciudades';

    protected $fillable = [
        'uuid',
        'pais_id',
        'nombre',
        'codigo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
