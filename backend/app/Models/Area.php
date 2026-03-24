<?php

declare(strict_types=1);

namespace App\Models;

class Area extends BaseModel
{
    protected $table = 'areas';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'division_id',
        'nombre',
        'descripcion',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }
}
