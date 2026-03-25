<?php

namespace App\Core\Security;

use App\Core\Security\Models\CoreSecurityLog;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityLogger
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected Request $request,
    ) {
    }

    public function log(
        string $eventKey,
        ?User $actor = null,
        string $severity = 'info',
        ?string $summary = null,
        array $context = [],
        ?int $organizationId = null,
    ): CoreSecurityLog {
        return CoreSecurityLog::query()->create([
            'organizacion_id' => $organizationId ?? $this->tenantContext->organizationId($actor),
            'actor_id' => $actor?->id,
            'event_key' => $eventKey,
            'severity' => $severity,
            'ip_address' => $this->request->ip(),
            'request_id' => $this->request->attributes->get('request_id'),
            'summary' => $summary,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
