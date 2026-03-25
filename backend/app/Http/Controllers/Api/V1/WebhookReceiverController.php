<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Security\SecurityLogger;
use App\Core\Webhooks\Models\CoreWebhookReceiver;
use App\Core\Webhooks\WebhookManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWebhookReceiverRequest;
use App\Http\Requests\Api\V1\UpdateWebhookReceiverRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookReceiverController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected WebhookManager $webhooks,
        protected AuditLogger $auditLogger,
        protected SecurityLogger $securityLogger,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $catalog = $this->webhooks->catalog();
        $receivers = $this->webhooks->receivers();

        return $this->successResponse(
            data: $receivers->map(fn (CoreWebhookReceiver $receiver): array => $this->webhooks->serializeReceiver($receiver))->all(),
            message: 'Receivers de webhook listados',
            meta: [
                'total' => $receivers->count(),
                'catalog' => $catalog->all(),
            ],
        );
    }

    public function receipts(Request $request): JsonResponse
    {
        $receipts = $this->webhooks->receipts();

        return $this->successResponse(
            data: $receipts->map(fn ($receipt): array => $this->webhooks->serializeReceipt($receipt))->all(),
            message: 'Recepciones de webhook listadas',
            meta: [
                'total' => $receipts->count(),
            ],
        );
    }

    public function store(StoreWebhookReceiverRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->catalogHasEvent($request->string('module_key')->toString(), $request->string('event_key')->toString())) {
            return $this->errorResponse(
                message: 'El evento indicado no forma parte del contrato modular declarado.',
                status: 422,
            );
        }

        $receiver = $this->webhooks->createReceiver([
            'organizacion_id' => $user->organizacion_activa_id,
            'module_key' => $request->string('module_key')->toString(),
            'event_key' => $request->string('event_key')->toString(),
            'source_name' => $request->string('source_name')->toString(),
            'signing_secret' => $request->string('signing_secret')->toString(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogger->record(
            eventKey: 'webhook.receiver.created',
            actor: $user,
            entityType: 'core_webhook_receiver',
            entityKey: (string) $receiver->id,
            summary: 'Se registro un receiver de webhook',
            sourceModule: 'core-platform',
            context: [
                'module_key' => $receiver->module_key,
                'event_key' => $receiver->event_key,
                'source_name' => $receiver->source_name,
            ],
        );

        return $this->successResponse(
            data: $this->webhooks->serializeReceiver($receiver),
            message: 'Receiver de webhook creado',
        );
    }

    public function update(UpdateWebhookReceiverRequest $request, CoreWebhookReceiver $receiver): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->catalogHasEvent($request->string('module_key')->toString(), $request->string('event_key')->toString())) {
            return $this->errorResponse(
                message: 'El evento indicado no forma parte del contrato modular declarado.',
                status: 422,
            );
        }

        $receiver = $this->webhooks->updateReceiver($receiver, [
            'module_key' => $request->string('module_key')->toString(),
            'event_key' => $request->string('event_key')->toString(),
            'source_name' => $request->string('source_name')->toString(),
            'signing_secret' => $request->input('signing_secret'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogger->record(
            eventKey: 'webhook.receiver.updated',
            actor: $user,
            entityType: 'core_webhook_receiver',
            entityKey: (string) $receiver->id,
            summary: 'Se actualizo un receiver de webhook',
            sourceModule: 'core-platform',
            context: [
                'module_key' => $receiver->module_key,
                'event_key' => $receiver->event_key,
                'source_name' => $receiver->source_name,
            ],
        );

        return $this->successResponse(
            data: $this->webhooks->serializeReceiver($receiver),
            message: 'Receiver de webhook actualizado',
        );
    }

    public function receive(Request $request, CoreWebhookReceiver $receiver): JsonResponse
    {
        if (! $receiver->is_active) {
            return $this->errorResponse(
                message: 'Receiver de webhook inactivo',
                status: 404,
            );
        }

        $receipt = $this->webhooks->receive($receiver);

        if ($receipt->signature_status !== 'valid') {
            $this->securityLogger->log(
                eventKey: 'security.webhook_incoming_rejected',
                actor: null,
                severity: 'warning',
                summary: 'Se rechazo un webhook entrante por firma invalida.',
                context: [
                    'receiver_id' => $receiver->id,
                    'module_key' => $receiver->module_key,
                    'event_key' => $receiver->event_key,
                ],
                organizationId: $receiver->organizacion_id,
            );

            return $this->errorResponse(
                message: 'Firma invalida',
                meta: [
                    'receipt_id' => $receipt->id,
                ],
                status: 401,
            );
        }

        $this->auditLogger->record(
            eventKey: 'webhook.incoming.received',
            actor: null,
            entityType: 'core_webhook_receiver',
            entityKey: (string) $receiver->id,
            summary: 'Se recibio un webhook entrante',
            sourceModule: $receiver->module_key,
            context: [
                'event_key' => $receiver->event_key,
                'source_name' => $receiver->source_name,
                'receipt_id' => $receipt->id,
            ],
            organizationId: $receiver->organizacion_id,
        );

        return $this->successResponse(
            data: $this->webhooks->serializeReceipt($receipt),
            message: 'Webhook recibido correctamente',
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
}
