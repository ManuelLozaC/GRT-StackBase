<?php

namespace App\Core\Audit\Services;

use App\Core\Audit\Models\AuditLog;
use App\Core\Tenancy\TenantContext;
use App\Models\User;

class AuditLogger
{
    public function __construct(
        protected TenantContext $tenantContext,
    ) {
    }

    public function record(
        string $eventKey,
        ?User $actor = null,
        ?string $entityType = null,
        ?string $entityKey = null,
        ?string $summary = null,
        ?string $sourceModule = null,
        array $context = [],
        ?int $organizationId = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'organizacion_id' => $organizationId ?? $this->tenantContext->organizationId($actor),
            'actor_id' => $actor?->id,
            'event_key' => $eventKey,
            'entity_type' => $entityType,
            'entity_key' => $entityKey,
            'source_module' => $sourceModule,
            'summary' => $summary,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
