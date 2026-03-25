<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Metrics\MetricsRecorder;
use App\Core\Security\SecurityLogger;
use App\Core\Webhooks\Models\CoreWebhookEndpoint;
use App\Core\Webhooks\WebhookManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWebhookEndpointRequest;
use App\Http\Requests\Api\V1\TestWebhookEndpointRequest;
use App\Http\Requests\Api\V1\UpdateWebhookEndpointRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected WebhookManager $webhooks,
        protected AuditLogger $auditLogger,
        protected SecurityLogger $securityLogger,
        protected MetricsRecorder $metrics,
    ) {
    }

    public function endpoints(Request $request): JsonResponse
    {
        $catalog = $this->webhooks->catalog();
        $endpoints = $this->webhooks->endpoints();

        return $this->successResponse(
            data: $endpoints->map(fn (CoreWebhookEndpoint $endpoint): array => $this->webhooks->serializeEndpoint($endpoint))->all(),
            message: 'Endpoints de webhook listados',
            meta: [
                'total' => $endpoints->count(),
                'catalog' => $catalog->all(),
            ],
        );
    }

    public function deliveries(Request $request): JsonResponse
    {
        $deliveries = $this->webhooks->deliveries();

        return $this->successResponse(
            data: $deliveries->map(fn ($delivery): array => $this->webhooks->serializeDelivery($delivery))->all(),
            message: 'Entregas de webhook listadas',
            meta: [
                'total' => $deliveries->count(),
            ],
        );
    }

    public function store(StoreWebhookEndpointRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->catalogHasEvent($request->string('module_key')->toString(), $request->string('event_key')->toString())) {
            return $this->errorResponse(
                message: 'El evento indicado no forma parte del contrato modular declarado.',
                status: 422,
            );
        }

        $endpoint = $this->webhooks->createEndpoint([
            'organizacion_id' => $user->organizacion_activa_id,
            'module_key' => $request->string('module_key')->toString(),
            'event_key' => $request->string('event_key')->toString(),
            'target_url' => $request->string('target_url')->toString(),
            'signing_secret' => $request->string('signing_secret')->toString(),
            'custom_headers' => $this->normalizeHeaders($request->input('custom_headers', [])),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogger->record(
            eventKey: 'webhook.endpoint.created',
            actor: $user,
            entityType: 'core_webhook_endpoint',
            entityKey: (string) $endpoint->id,
            summary: 'Se registro un endpoint de webhook',
            sourceModule: 'core-platform',
            context: [
                'module_key' => $endpoint->module_key,
                'event_key' => $endpoint->event_key,
            ],
        );
        $this->securityLogger->log(
            eventKey: 'security.webhook_endpoint_created',
            actor: $user,
            severity: 'info',
            summary: 'Se registro un endpoint de webhook saliente.',
            context: [
                'endpoint_id' => $endpoint->id,
                'module_key' => $endpoint->module_key,
                'event_key' => $endpoint->event_key,
            ],
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'webhook.endpoint.created',
            eventCategory: 'integrations',
            actor: $user,
            context: [
                'endpoint_id' => $endpoint->id,
                'module_key' => $endpoint->module_key,
                'event_key' => $endpoint->event_key,
            ],
        );

        return $this->successResponse(
            data: $this->webhooks->serializeEndpoint($endpoint),
            message: 'Endpoint de webhook creado',
        );
    }

    public function update(UpdateWebhookEndpointRequest $request, CoreWebhookEndpoint $endpoint): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->catalogHasEvent($request->string('module_key')->toString(), $request->string('event_key')->toString())) {
            return $this->errorResponse(
                message: 'El evento indicado no forma parte del contrato modular declarado.',
                status: 422,
            );
        }

        $endpoint = $this->webhooks->updateEndpoint($endpoint, [
            'module_key' => $request->string('module_key')->toString(),
            'event_key' => $request->string('event_key')->toString(),
            'target_url' => $request->string('target_url')->toString(),
            'signing_secret' => $request->input('signing_secret'),
            'custom_headers' => $this->normalizeHeaders($request->input('custom_headers', [])),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogger->record(
            eventKey: 'webhook.endpoint.updated',
            actor: $user,
            entityType: 'core_webhook_endpoint',
            entityKey: (string) $endpoint->id,
            summary: 'Se actualizo un endpoint de webhook',
            sourceModule: 'core-platform',
            context: [
                'module_key' => $endpoint->module_key,
                'event_key' => $endpoint->event_key,
            ],
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'webhook.endpoint.updated',
            eventCategory: 'integrations',
            actor: $user,
            context: [
                'endpoint_id' => $endpoint->id,
            ],
        );

        return $this->successResponse(
            data: $this->webhooks->serializeEndpoint($endpoint),
            message: 'Endpoint de webhook actualizado',
        );
    }

    public function test(TestWebhookEndpointRequest $request, CoreWebhookEndpoint $endpoint): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $delivery = $this->webhooks->testEndpoint(
            endpoint: $endpoint,
            payload: $request->input('payload', [
                'mode' => 'test',
                'sent_at' => now()->toIso8601String(),
                'organization_id' => $user->organizacion_activa_id,
                'event_key' => $endpoint->event_key,
            ]),
            actor: $user,
        );

        $this->auditLogger->record(
            eventKey: 'webhook.endpoint.tested',
            actor: $user,
            entityType: 'core_webhook_endpoint',
            entityKey: (string) $endpoint->id,
            summary: 'Se ejecuto una entrega de prueba de webhook',
            sourceModule: 'core-platform',
            context: [
                'delivery_status' => $delivery->status,
                'response_status' => $delivery->response_status,
            ],
        );
        $this->securityLogger->log(
            eventKey: 'security.webhook_endpoint_tested',
            actor: $user,
            severity: $delivery->status === 'succeeded' ? 'info' : 'warning',
            summary: 'Se ejecuto una prueba manual de webhook.',
            context: [
                'endpoint_id' => $endpoint->id,
                'delivery_id' => $delivery->id,
                'status' => $delivery->status,
            ],
        );

        return $this->successResponse(
            data: $this->webhooks->serializeDelivery($delivery),
            message: 'Prueba de webhook ejecutada',
        );
    }

    protected function catalogHasEvent(string $moduleKey, string $eventKey): bool
    {
        return $this->webhooks->catalog()
            ->contains(function (array $module) use ($moduleKey, $eventKey): bool {
                if (($module['key'] ?? null) !== $moduleKey) {
                    return false;
                }

                return collect($module['events'] ?? [])
                    ->contains(fn (array $event): bool => ($event['key'] ?? null) === $eventKey);
            });
    }

    protected function normalizeHeaders(array $headers): array
    {
        return collect($headers)
            ->filter(fn (mixed $value, mixed $key): bool => is_string($key) && trim($key) !== '' && filled($value))
            ->mapWithKeys(fn (mixed $value, mixed $key): array => [trim((string) $key) => trim((string) $value)])
            ->all();
    }
}
