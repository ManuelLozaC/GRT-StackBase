<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\GeneraUuid;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use GeneraUuid;
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use MultiTenantable;
    use Notifiable;
    use SoftDeletes;

    protected $guard_name = 'sanctum';

    protected $fillable = [
        'uuid',
        'organizacion_id',
        'persona_id',
        'alias',
        'nombre_mostrar',
        'email',
        'telefono',
        'password',
        'es_superusuario',
        'debe_cambiar_password',
        'activo',
        'ultimo_acceso_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'es_superusuario' => 'boolean',
            'debe_cambiar_password' => 'boolean',
            'activo' => 'boolean',
            'ultimo_acceso_at' => 'datetime',
        ];
    }

    public function organizacion()
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function asignacionesLaborales()
    {
        return $this->hasMany(AsignacionLaboral::class, 'usuario_id');
    }

    public function esSuperusuario(): bool
    {
        return (bool) $this->es_superusuario;
    }
}
