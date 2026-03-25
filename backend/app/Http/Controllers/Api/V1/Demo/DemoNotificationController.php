<?php

namespace App\Http\Controllers\Api\V1\Demo;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Metrics\MetricsRecorder;
use App\Core\Modules\ModuleSettingsManager;
use App\Core\Notifications\Services\NotificationCenter;
use App\Core\Webhooks\WebhookManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Demo\StoreDemoNotificationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DemoNotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NotificationCenter $notifications,
        protected AuditLogger $auditLogger,
        protected ModuleSettingsManager $moduleSettings,
        protected MetricsRecorder $metrics,
        protected WebhookManager $webhooks,
    ) {
    }

    public function store(StoreDemoNotificationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->notifications->createMultichannel(
            recipient: $user,
            title: $request->string('title')->toString(),
            message: $request->string('message')->toString(),
            level: $request->string('level')->toString()
                ?: (string) $this->moduleSettings->get('demo-platform', 'notification_default_level', 'info'),
            creator: $user,
            actionUrl: $request->string('action_url')->toString() ?: null,
            metadata: [
                'source' => 'demo-platform',
            ],
            channels: $request->input('channels', ['internal']),
        );
        $notification = $result['notification'];
        $deliveries = $result['deliveries'];

        $this->auditLogger->record(
            eventKey: 'demo.notification.created',
            actor: $user,
            entityType: 'core_notification',
            entityKey: $notification?->uuid ?? 'multichannel-demo',
            summary: 'Se genero una notificacion demo',
            sourceModule: 'demo-platform',
            context: [
                'level' => $notification?->level,
                'title' => $notification?->title ?? $request->string('title')->toString(),
                'channels' => $deliveries->pluck('channel')->all(),
            ],
        );
        $this->metrics->record(
            moduleKey: 'demo-platform',
            eventKey: 'demo.notification.created',
            eventCategory: 'notifications',
            actor: $user,
            context: [
                'channels' => $deliveries->pluck('channel')->all(),
            ],
        );
        $this->webhooks->dispatch(
            moduleKey: 'demo-platform',
            eventKey: 'demo.notification.created',
            payload: [
                'notification' => [
                    'uuid' => $notification?->uuid,
                    'title' => $notification?->title ?? $request->string('title')->toString(),
                    'level' => $notification?->level ?? $request->string('level')->toString(),
                    'channels' => $deliveries->pluck('channel')->all(),
                ],
                'created_by' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
                'occurred_at' => now()->toIso8601String(),
            ],
            actor: $user,
        );

        return $this->successResponse(
            data: [
                'uuid' => $notification?->uuid,
                'title' => $notification?->title ?? $request->string('title')->toString(),
                'message' => $notification?->message ?? $request->string('message')->toString(),
                'level' => $notification?->level ?? $request->string('level')->toString(),
                'deliveries' => $deliveries->map(fn ($delivery): array => [
                    'channel' => $delivery->channel,
                    'status' => $delivery->status,
                    'destination' => $delivery->destination,
                    'status_detail' => $delivery->status_detail,
                    'processed_at' => $delivery->processed_at?->toIso8601String(),
                ])->values()->all(),
                'read_at' => $notification?->read_at?->toIso8601String(),
                'created_at' => $notification?->created_at?->toIso8601String(),
            ],
            message: 'Notificacion demo creada',
        );
    }
}
