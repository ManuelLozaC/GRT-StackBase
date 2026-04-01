<?php

namespace App\Core\Audit\Services;

use App\Core\Audit\Models\AuditLog;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected Request $request,
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
        $resolvedOrganizationId = $organizationId ?? $this->tenantContext->companyId($actor);
        $resolvedActorId = $actor?->id ?? $this->tenantContext->actorId();
        $resolvedContext = $this->contextWithRequestMetadata($context);

        if (! $this->enabled()) {
            return new AuditLog([
                'organizacion_id' => $resolvedOrganizationId,
                'actor_id' => $resolvedActorId,
                'event_key' => $eventKey,
                'entity_type' => $entityType,
                'entity_key' => $entityKey,
                'source_module' => $sourceModule,
                'summary' => $summary,
                'context' => $resolvedContext,
                'occurred_at' => now(),
            ]);
        }

        return AuditLog::query()->create([
            'organizacion_id' => $resolvedOrganizationId,
            'actor_id' => $resolvedActorId,
            'event_key' => $eventKey,
            'entity_type' => $entityType,
            'entity_key' => $entityKey,
            'source_module' => $sourceModule,
            'summary' => $summary,
            'context' => $resolvedContext,
            'occurred_at' => now(),
        ]);
    }

    protected function contextWithRequestMetadata(array $context): array
    {
        $requestId = $this->request->attributes->get('request_id');

        if (! $requestId || array_key_exists('request_id', $context)) {
            return $context;
        }

        return array_merge($context, [
            'request_id' => $requestId,
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
