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
        if (! $this->enabled()) {
            return new AuditLog([
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

    protected function enabled(): bool
    {
        return filter_var(
            env('CORE_AUDIT_LOGS_ENABLED', true),
            FILTER_VALIDATE_BOOL,
        );
    }
}
