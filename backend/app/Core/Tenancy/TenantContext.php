<?php

namespace App\Core\Tenancy;

use App\Models\User;

class TenantContext
{
    protected ?int $organizationId = null;

    public function setOrganizationId(?int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function setFromUser(?User $user): void
    {
        $this->organizationId = $user?->organizacion_activa_id;
    }

    public function organizationId(?User $fallbackUser = null): ?int
    {
        return $this->organizationId ?? $fallbackUser?->organizacion_activa_id;
    }

    public function clear(): void
    {
        $this->organizationId = null;
    }
}
