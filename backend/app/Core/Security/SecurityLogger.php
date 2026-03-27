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
        if (! $this->enabledFor($severity)) {
            return new CoreSecurityLog([
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

    protected function enabledFor(string $severity): bool
    {
        $securityLogsEnabled = filter_var(
            env('CORE_SECURITY_LOGS_ENABLED', true),
            FILTER_VALIDATE_BOOL,
        );

        if (! $securityLogsEnabled) {
            return false;
        }

        if ($severity !== 'info') {
            return true;
        }

        return filter_var(
            env('CORE_SECURITY_INFO_LOGS_ENABLED', app()->environment('production')),
            FILTER_VALIDATE_BOOL,
        );
    }
}
