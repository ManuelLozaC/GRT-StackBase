<?php

namespace App\Core\Tenancy;

use App\Models\User;

class TenantContext
{
    protected ?int $companyId = null;
    protected ?int $actorId = null;
    protected ?int $workAssignmentId = null;

    public function setCompanyId(?int $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function setOrganizationId(?int $organizationId): void
    {
        $this->setCompanyId($organizationId);
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
        $this->companyId = $user?->activeCompanyId();
        $this->actorId = $user?->id;
        $this->workAssignmentId = $user?->activeWorkAssignmentId();
    }

    public function companyId(?User $fallbackUser = null): ?int
    {
        return $this->companyId ?? $fallbackUser?->activeCompanyId();
    }

    public function organizationId(?User $fallbackUser = null): ?int
    {
        return $this->companyId($fallbackUser);
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
        $companyId = $this->companyId($fallbackUser);
        $workAssignmentId = $this->workAssignmentId($fallbackUser);

        return [
            'empresa_id' => $companyId,
            'company_id' => $companyId,
            'organizacion_id' => $companyId,
            'actor_id' => $this->actorId($fallbackUser),
            'asignacion_laboral_id' => $workAssignmentId,
            'active_work_assignment_id' => $workAssignmentId,
        ];
    }

    public function clear(): void
    {
        $this->companyId = null;
        $this->actorId = null;
        $this->workAssignmentId = null;
    }
}
