<?php

namespace App\Core\Webhooks;

use App\Core\Metrics\MetricsRecorder;
use App\Core\Modules\ModuleRegistry;
use App\Core\Webhooks\Models\CoreWebhookDelivery;
use App\Core\Webhooks\Models\CoreWebhookEndpoint;
use App\Core\Webhooks\Models\CoreWebhookReceipt;
use App\Core\Webhooks\Models\CoreWebhookReceiver;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class WebhookManager
{
    public function __construct(
        protected MetricsRecorder $metrics,
        protected Request $request,
        protected ModuleRegistry $modules,
    ) {
    }

    public function catalog(): Collection
    {
        return $this->modules->all()
            ->map(function (array $module): ?array {
                $events = collect($module['webhooks'] ?? [])
                    ->filter(fn (mixed $event): bool => is_array($event) && isset($event['key']))
                    ->map(fn (array $event): array => [
                        'key' => $event['key'],
                        'label' => $event['label'] ?? $event['key'],
                        'description' => $event['description'] ?? null,
                    ])
                    ->values()
                    ->all();

                if ($events === []) {
                    return null;
                }

                return [
                    'key' => $module['key'],
                    'name' => $module['name'],
                    'description' => $module['description'] ?? null,
                    'events' => $events,
                ];
            })
            ->filter()
            ->values();
    }

    public function endpoints(?string $moduleKey = null): Collection
    {
        return CoreWebhookEndpoint::query()
            ->when($moduleKey, fn ($query) => $query->where('module_key', $moduleKey))
            ->latest('id')
            ->get();
    }

    public function receivers(?string $moduleKey = null): Collection
    {
        return CoreWebhookReceiver::query()
            ->when($moduleKey, fn ($query) => $query->where('module_key', $moduleKey))
            ->latest('id')
            ->get();
    }

    public function deliveries(?string $moduleKey = null): Collection
    {
        return CoreWebhookDelivery::query()
            ->with('actor:id,name,email')
            ->when($moduleKey, fn ($query) => $query->where('module_key', $moduleKey))
            ->latest('id')
            ->limit(100)
            ->get();
    }

    public function receipts(?string $moduleKey = null): Collection
    {
        return CoreWebhookReceipt::query()
            ->with('receiver:id,source_name')
            ->when($moduleKey, fn ($query) => $query->where('module_key', $moduleKey))
            ->latest('id')
            ->limit(100)
            ->get();
    }

    public function createEndpoint(array $payload): CoreWebhookEndpoint
    {
        return CoreWebhookEndpoint::query()->create($payload);
    }

    public function createReceiver(array $payload): CoreWebhookReceiver
    {
        return CoreWebhookReceiver::query()->create($payload);
    }

    public function updateEndpoint(CoreWebhookEndpoint $endpoint, array $payload): CoreWebhookEndpoint
    {
        if (($payload['signing_secret'] ?? null) === null || ($payload['signing_secret'] ?? '') === '') {
            unset($payload['signing_secret']);
        }

        $endpoint->fill($payload)->save();

        return $endpoint->fresh();
    }

    public function updateReceiver(CoreWebhookReceiver $receiver, array $payload): CoreWebhookReceiver
    {
        if (($payload['signing_secret'] ?? null) === null || ($payload['signing_secret'] ?? '') === '') {
            unset($payload['signing_secret']);
        }

        $receiver->fill($payload)->save();

        return $receiver->fresh();
    }

    public function dispatch(string $moduleKey, string $eventKey, array $payload, ?User $actor = null): Collection
    {
        $endpoints = CoreWebhookEndpoint::query()
            ->where('module_key', $moduleKey)
            ->where('event_key', $eventKey)
            ->where('is_active', true)
            ->get();

        return $endpoints->map(fn (CoreWebhookEndpoint $endpoint): CoreWebhookDelivery => $this->dispatchToEndpoint($endpoint, $payload, $actor));
    }

    public function testEndpoint(CoreWebhookEndpoint $endpoint, array $payload, ?User $actor = null): CoreWebhookDelivery
    {
        return $this->dispatchToEndpoint($endpoint, $payload, $actor);
    }

    public function serializeEndpoint(CoreWebhookEndpoint $endpoint): array
    {
        return [
            'id' => $endpoint->id,
            'module_key' => $endpoint->module_key,
            'event_key' => $endpoint->event_key,
            'target_url' => $endpoint->target_url,
            'custom_headers' => $endpoint->custom_headers ?? [],
            'is_active' => $endpoint->is_active,
            'last_delivered_at' => $endpoint->last_delivered_at?->toIso8601String(),
            'secret_mask' => Str::mask($endpoint->signing_secret ?? '', '*', 3),
            'created_at' => $endpoint->created_at?->toIso8601String(),
        ];
    }

    public function serializeReceiver(CoreWebhookReceiver $receiver): array
    {
        return [
            'id' => $receiver->id,
            'module_key' => $receiver->module_key,
            'event_key' => $receiver->event_key,
            'source_name' => $receiver->source_name,
            'is_active' => $receiver->is_active,
            'last_received_at' => $receiver->last_received_at?->toIso8601String(),
            'secret_mask' => Str::mask($receiver->signing_secret ?? '', '*', 3),
            'created_at' => $receiver->created_at?->toIso8601String(),
        ];
    }

    public function serializeDelivery(CoreWebhookDelivery $delivery): array
    {
        return [
            'id' => $delivery->id,
            'module_key' => $delivery->module_key,
            'event_key' => $delivery->event_key,
            'target_url' => $delivery->target_url,
            'status' => $delivery->status,
            'response_status' => $delivery->response_status,
            'error_message' => $delivery->error_message,
            'request_id' => $delivery->request_id,
            'delivered_at' => $delivery->delivered_at?->toIso8601String(),
            'actor' => $delivery->actor ? [
                'id' => $delivery->actor->id,
                'name' => $delivery->actor->name,
                'email' => $delivery->actor->email,
            ] : null,
        ];
    }

    public function serializeReceipt(CoreWebhookReceipt $receipt): array
    {
        return [
            'id' => $receipt->id,
            'module_key' => $receipt->module_key,
            'event_key' => $receipt->event_key,
            'source_name' => $receipt->source_name,
            'signature_status' => $receipt->signature_status,
            'processing_status' => $receipt->processing_status,
            'request_id' => $receipt->request_id,
            'ip_address' => $receipt->ip_address,
            'received_at' => $receipt->received_at?->toIso8601String(),
            'receiver' => $receipt->receiver ? [
                'id' => $receipt->receiver->id,
                'source_name' => $receipt->receiver->source_name,
            ] : null,
        ];
    }

    public function receive(CoreWebhookReceiver $receiver): CoreWebhookReceipt
    {
        $rawBody = $this->request->getContent();
        $payload = $this->request->all();
        $signatureHeader = $this->request->header('X-StackBase-Signature', '');
        $timestampHeader = trim((string) $this->request->header('X-StackBase-Timestamp', ''));
        $requestId = trim((string) $this->request->header('X-StackBase-Request-Id', ''))
            ?: ($this->request->attributes->get('request_id') ?: (string) Str::uuid());
        $signedPayload = $timestampHeader !== '' ? $timestampHeader.'.'.$rawBody : $rawBody;
        $expectedSignature = hash_hmac('sha256', $signedPayload, (string) $receiver->signing_secret);
        $providedSignature = $this->normalizeIncomingSignature($signatureHeader);
        $signatureStatus = hash_equals($expectedSignature, $providedSignature) ? 'valid' : 'invalid';
        $processingStatus = $signatureStatus === 'valid' ? 'accepted' : 'rejected';

        if ($signatureStatus === 'valid' && ! $this->isFreshWebhookTimestamp($timestampHeader)) {
            $signatureStatus = 'expired';
            $processingStatus = 'rejected';
        }

        if ($processingStatus === 'accepted' && ! $this->reserveIncomingWebhookRequest($receiver, $requestId)) {
            $signatureStatus = 'replayed';
            $processingStatus = 'rejected';
        }

        $receipt = CoreWebhookReceipt::query()->create([
            'organizacion_id' => $receiver->organizacion_id,
            'receiver_id' => $receiver->id,
            'module_key' => $receiver->module_key,
            'event_key' => $receiver->event_key,
            'source_name' => $receiver->source_name,
            'signature_status' => $signatureStatus,
            'processing_status' => $processingStatus,
            'request_id' => $requestId,
            'ip_address' => $this->request->ip(),
            'request_headers' => $this->request->headers->all(),
            'request_body' => is_array($payload) && $payload !== [] ? $payload : ['raw' => $rawBody],
            'received_at' => now(),
        ]);

        if ($processingStatus === 'accepted') {
            $receiver->forceFill([
                'last_received_at' => now(),
            ])->save();
        }

        $this->metrics->record(
            moduleKey: $receiver->module_key,
            eventKey: 'webhook.received.'.$receipt->processing_status,
            eventCategory: 'integrations',
            actor: null,
            context: [
                'event_key' => $receiver->event_key,
                'receiver_id' => $receiver->id,
                'signature_status' => $signatureStatus,
            ],
            organizationId: $receiver->organizacion_id,
        );

        return $receipt->fresh('receiver:id,source_name');
    }

    protected function normalizeIncomingSignature(?string $signatureHeader): string
    {
        $signature = trim((string) $signatureHeader);

        if (str_starts_with($signature, 'sha256=')) {
            return substr($signature, 7);
        }

        return $signature;
    }

    protected function dispatchToEndpoint(CoreWebhookEndpoint $endpoint, array $payload, ?User $actor = null): CoreWebhookDelivery
    {
        $requestId = $this->request->attributes->get('request_id') ?: (string) Str::uuid();
        $timestamp = (string) now()->timestamp;
        $rawPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        $signature = hash_hmac('sha256', $timestamp.'.'.$rawPayload, (string) $endpoint->signing_secret);
        $headers = array_merge($endpoint->custom_headers ?? [], [
            'X-StackBase-Event' => $endpoint->event_key,
            'X-StackBase-Module' => $endpoint->module_key,
            'X-StackBase-Request-Id' => $requestId,
            'X-StackBase-Timestamp' => $timestamp,
            'X-StackBase-Signature' => $signature,
        ]);

        $delivery = CoreWebhookDelivery::query()->create([
            'organizacion_id' => $endpoint->organizacion_id,
            'endpoint_id' => $endpoint->id,
            'actor_id' => $actor?->id,
            'module_key' => $endpoint->module_key,
            'event_key' => $endpoint->event_key,
            'target_url' => $endpoint->target_url,
            'request_headers' => $headers,
            'request_body' => $payload,
            'status' => 'pending',
            'request_id' => $requestId,
        ]);

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($endpoint->target_url, $payload);

            $delivery->forceFill([
                'status' => $response->successful() ? 'succeeded' : 'failed',
                'response_status' => $response->status(),
                'response_body' => Str::limit($response->body(), 2000),
                'delivered_at' => now(),
            ])->save();

            $endpoint->forceFill([
                'last_delivered_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $delivery->forceFill([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'delivered_at' => now(),
            ])->save();
        }

        $this->metrics->record(
            moduleKey: $endpoint->module_key,
            eventKey: 'webhook.delivery.'.$delivery->status,
            eventCategory: 'webhooks',
            actor: $actor,
            context: [
                'event_key' => $endpoint->event_key,
                'target_url' => $endpoint->target_url,
                'status' => $delivery->status,
                'response_status' => $delivery->response_status,
            ],
            organizationId: $endpoint->organizacion_id,
        );

        return $delivery->fresh('actor:id,name,email');
    }

    protected function isFreshWebhookTimestamp(string $timestampHeader): bool
    {
        if (! (bool) config('security.channels.webhooks.require_timestamp', config('security.webhooks.require_timestamp', true))) {
            return true;
        }

        if (! ctype_digit($timestampHeader)) {
            return false;
        }

        $timestamp = (int) $timestampHeader;
        $window = max(30, (int) config('security.channels.webhooks.replay_window_seconds', config('security.webhooks.replay_window_seconds', 300)));

        return abs(now()->timestamp - $timestamp) <= $window;
    }

    protected function reserveIncomingWebhookRequest(CoreWebhookReceiver $receiver, string $requestId): bool
    {
        $window = max(30, (int) config('security.channels.webhooks.replay_window_seconds', config('security.webhooks.replay_window_seconds', 300)));
        $cacheKey = sprintf('webhook-replay:%s:%s', $receiver->id, sha1($requestId));

        return Cache::add($cacheKey, true, now()->addSeconds($window));
    }
}
