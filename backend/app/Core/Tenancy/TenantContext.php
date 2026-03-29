<?php

namespace App\Core\Tenancy;

use App\Models\User;

class TenantContext
{
    protected ?int $organizationId = null;
    protected ?int $actorId = null;

    public function setOrganizationId(?int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function setActorId(?int $actorId): void
    {
        $this->actorId = $actorId;
    }

    public function setFromUser(?User $user): void
    {
        $this->organizationId = $user?->organizacion_activa_id;
        $this->actorId = $user?->id;
    }

    public function organizationId(?User $fallbackUser = null): ?int
    {
        return $this->organizationId ?? $fallbackUser?->organizacion_activa_id;
    }

    public function actorId(?User $fallbackUser = null): ?int
    {
        return $this->actorId ?? $fallbackUser?->id;
    }

    public function snapshot(?User $fallbackUser = null): array
    {
        return [
            'organizacion_id' => $this->organizationId($fallbackUser),
            'actor_id' => $this->actorId($fallbackUser),
        ];
    }

    public function clear(): void
    {
        $this->organizationId = null;
        $this->actorId = null;
    }
}
