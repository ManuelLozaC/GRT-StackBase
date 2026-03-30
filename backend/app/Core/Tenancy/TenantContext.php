<?php

namespace App\Core\Tenancy;

use App\Models\User;

class TenantContext
{
    protected ?int $organizationId = null;
    protected ?int $actorId = null;
    protected ?int $workAssignmentId = null;

    public function setOrganizationId(?int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function setActorId(?int $actorId): void
    {
        $this->actorId = $actorId;
    }

    public function setWorkAssignmentId(?int $workAssignmentId): void
    {
        $this->workAssignmentId = $workAssignmentId;
    }

    public function setFromUser(?User $user): void
    {
        $this->organizationId = $user?->activeOrganizationId();
        $this->actorId = $user?->id;
        $this->workAssignmentId = $user?->activeWorkAssignmentId();
    }

    public function organizationId(?User $fallbackUser = null): ?int
    {
        return $this->organizationId ?? $fallbackUser?->activeOrganizationId();
    }

    public function companyId(?User $fallbackUser = null): ?int
    {
        return $this->organizationId($fallbackUser);
    }

    public function actorId(?User $fallbackUser = null): ?int
    {
        return $this->actorId ?? $fallbackUser?->id;
    }

    public function workAssignmentId(?User $fallbackUser = null): ?int
    {
        return $this->workAssignmentId ?? $fallbackUser?->activeWorkAssignmentId();
    }

    public function snapshot(?User $fallbackUser = null): array
    {
        $organizationId = $this->organizationId($fallbackUser);
        $workAssignmentId = $this->workAssignmentId($fallbackUser);

        return [
            'organizacion_id' => $organizationId,
            'empresa_id' => $organizationId,
            'company_id' => $organizationId,
            'actor_id' => $this->actorId($fallbackUser),
            'asignacion_laboral_id' => $workAssignmentId,
            'active_work_assignment_id' => $workAssignmentId,
        ];
    }

    public function clear(): void
    {
        $this->organizationId = null;
        $this->actorId = null;
        $this->workAssignmentId = null;
    }
}
