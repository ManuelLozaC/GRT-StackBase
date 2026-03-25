<?php

<<<<<<< HEAD
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organizacion extends Model
{
    use HasFactory;
=======
declare(strict_types=1);

namespace App\Models;

class Organizacion extends BaseModel
{
    protected static bool $omitirAislamientoTenant = true;
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3

    protected $table = 'organizaciones';

    protected $fillable = [
<<<<<<< HEAD
        'nombre',
        'slug',
        'metadata',
=======
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
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
    ];

    protected function casts(): array
    {
        return [
<<<<<<< HEAD
            'metadata' => 'array',
        ];
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    public function usuariosActivos(): HasMany
    {
        return $this->hasMany(User::class, 'organizacion_activa_id');
    }
=======
            'activa' => 'boolean',
        ];
    }
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
}
