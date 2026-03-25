<?php

namespace App\Core\Metrics;

use App\Core\Metrics\Models\CoreMetricEvent;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Http\Request;

class MetricsRecorder
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected Request $request,
    ) {
    }

    public function record(
        string $moduleKey,
        string $eventKey,
        string $eventCategory,
        ?User $actor = null,
        array $context = [],
        ?int $organizationId = null,
    ): CoreMetricEvent {
        return CoreMetricEvent::query()->create([
            'organizacion_id' => $organizationId ?? $this->tenantContext->organizationId($actor),
            'actor_id' => $actor?->id,
            'module_key' => $moduleKey,
            'event_key' => $eventKey,
            'event_category' => $eventCategory,
            'request_id' => $this->request->attributes->get('request_id'),
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
