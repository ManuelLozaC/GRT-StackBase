<?php

declare(strict_types=1);

namespace App\Models;

class Division extends BaseModel
{
    protected $table = 'divisiones';

    protected $fillable = [
        'uuid',
        'organizacion_id',
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
