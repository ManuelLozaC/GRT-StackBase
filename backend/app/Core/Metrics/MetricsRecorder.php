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
        if (! $this->enabled()) {
            return new CoreMetricEvent([
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

    protected function enabled(): bool
    {
        return filter_var(
            env('CORE_METRICS_ENABLED', app()->environment('production')),
            FILTER_VALIDATE_BOOL,
        );
    }
}
