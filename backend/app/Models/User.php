<?php

declare(strict_types=1);

namespace App\Models;

<<<<<<< HEAD
use App\Core\Auth\Models\PersonalAccessToken;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
=======
use App\Traits\GeneraUuid;
use App\Traits\MultiTenantable;
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
<<<<<<< HEAD
        'name',
        'email',
        'password',
        'organizacion_activa_id',
=======
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
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
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

<<<<<<< HEAD
    public function accessTokens(): HasMany
    {
        return $this->hasMany(PersonalAccessToken::class);
    }

    public function organizaciones(): BelongsToMany
    {
        return $this->belongsToMany(Organizacion::class)
            ->withTimestamps();
    }

    public function organizacionActiva(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class, 'organizacion_activa_id');
=======
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
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
    }
}
