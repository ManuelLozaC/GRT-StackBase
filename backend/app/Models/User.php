<?php

namespace App\Models;

use App\Core\Auth\Models\PersonalAccessToken;
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
        'name',
        'email',
        'password',
        'organizacion_activa_id',
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

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class)
            ->withTimestamps();
    }
}
