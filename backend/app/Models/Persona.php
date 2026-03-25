<?php

declare(strict_types=1);

namespace App\Models;

class Persona extends BaseModel
{
    protected $table = 'personas';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'ciudad_id',
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'genero',
        'fecha_nacimiento',
        'email',
        'telefono',
        'direccion',
        'foto_path',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
        ];
    }
}
