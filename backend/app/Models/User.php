<?php

namespace App\Models;

use App\Core\Auth\Models\PersonalAccessToken;
use App\Core\Notifications\Models\CorePushSubscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'persona_id',
        'name',
        'alias',
        'email',
        'telefono',
        'password',
        'activo',
        'primer_acceso_pendiente',
        'expira_password_en',
        'organizacion_activa_id',
        'active_work_assignment_id',
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
            'activo' => 'boolean',
            'primer_acceso_pendiente' => 'boolean',
            'expira_password_en' => 'datetime',
        ];
    }

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
    }

    public function empresaActiva(): BelongsTo
    {
        return $this->organizacionActiva();
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class)
            ->withTimestamps();
    }

    public function asignacionesLaborales(): HasMany
    {
        return $this->hasMany(AsignacionLaboral::class, 'user_id');
    }

    public function asignacionLaboralActiva(): BelongsTo
    {
        return $this->belongsTo(AsignacionLaboral::class, 'active_work_assignment_id');
    }

    public function activeOrganizationId(): ?int
    {
        return $this->organizacion_activa_id;
    }

    public function activeCompanyId(): ?int
    {
        return $this->activeOrganizationId();
    }

    public function activeWorkAssignmentId(): ?int
    {
        return $this->active_work_assignment_id;
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(CorePushSubscription::class);
    }
}
