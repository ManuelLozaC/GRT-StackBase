<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Metrics\MetricsRecorder;
use App\Core\Notifications\Models\CoreNotification;
use App\Core\Notifications\Models\CoreNotificationDelivery;
use App\Core\Notifications\Services\NotificationCenter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NotificationCenter $notifications,
        protected AuditLogger $auditLogger,
        protected MetricsRecorder $metrics,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notifications = CoreNotification::query()
            ->with('deliveries')
            ->where('recipient_id', $user->id)
            ->latest('id')
            ->limit(50)
            ->get();

        return $this->successResponse(
            data: $notifications->map(fn (CoreNotification $notification): array => $this->transformNotification($notification))->all(),
            message: 'Notificaciones listadas',
            meta: [
                'total' => $notifications->count(),
                'unread_count' => $notifications->whereNull('read_at')->count(),
            ],
        );
    }

    public function deliveries(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $deliveries = CoreNotificationDelivery::query()
            ->with('notification:id,uuid,title,message,level,action_url')
            ->where('recipient_id', $user->id)
            ->latest('id')
            ->limit(50)
            ->get();

        return $this->successResponse(
            data: $deliveries->map(fn (CoreNotificationDelivery $delivery): array => $this->transformDelivery($delivery))->all(),
            message: 'Entregas listadas',
            meta: [
                'total' => $deliveries->count(),
            ],
        );
    }

    public function retryDelivery(Request $request, CoreNotificationDelivery $delivery): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ((int) $delivery->recipient_id !== (int) $user->id) {
            return $this->errorResponse(
                message: 'No tienes acceso a esta entrega',
                status: 403,
            );
        }

        $canRetry = (bool) data_get($delivery->metadata, 'retriable', false);

        if (! $canRetry || ! in_array($delivery->channel, ['email', 'push'], true)) {
            return $this->errorResponse(
                message: 'Esta entrega no admite reintentos manuales.',
                status: 422,
            );
        }

        $delivery = $this->notifications->retryDelivery($delivery);

        $this->auditLogger->record(
            eventKey: 'notification.delivery.retried',
            actor: $user,
            entityType: 'core_notification_delivery',
            entityKey: (string) $delivery->id,
            summary: 'Se reintento una entrega de notificacion.',
            sourceModule: 'core-platform',
            context: [
                'channel' => $delivery->channel,
                'status' => $delivery->status,
            ],
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'notification.delivery.retried',
            eventCategory: 'notifications',
            actor: $user,
            context: [
                'delivery_id' => $delivery->id,
                'channel' => $delivery->channel,
                'status' => $delivery->status,
            ],
        );

        return $this->successResponse(
            data: $this->transformDelivery($delivery),
            message: 'Entrega reenviada correctamente',
        );
    }

    public function markAsRead(Request $request, CoreNotification $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ((int) $notification->recipient_id !== (int) $user->id) {
            return $this->errorResponse(
                message: 'No tienes acceso a esta notificacion',
                status: 403,
            );
        }

        $notification = $this->notifications->markAsRead($notification);

        $this->auditLogger->record(
            eventKey: 'notification.read',
            actor: $user,
            entityType: 'core_notification',
            entityKey: $notification->uuid,
            summary: 'Se marco una notificacion como leida',
            sourceModule: 'core-platform',
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'notification.read',
            eventCategory: 'notifications',
            actor: $user,
            context: [
                'notification_uuid' => $notification->uuid,
            ],
        );

        return $this->successResponse(
            data: $this->transformNotification($notification),
            message: 'Notificacion marcada como leida',
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $updatedCount = $this->notifications->markAllAsReadFor($user);

        $this->auditLogger->record(
            eventKey: 'notification.read_all',
            actor: $user,
            entityType: 'core_notification',
            entityKey: 'bulk',
            summary: 'Se marcaron todas las notificaciones como leidas',
            sourceModule: 'core-platform',
            context: [
                'updated_count' => $updatedCount,
            ],
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'notification.read_all',
            eventCategory: 'notifications',
            actor: $user,
            context: [
                'updated_count' => $updatedCount,
            ],
        );

        return $this->successResponse(
            data: [
                'updated_count' => $updatedCount,
            ],
            message: 'Notificaciones marcadas como leidas',
        );
    }

    protected function transformNotification(CoreNotification $notification): array
    {
        return [
            'uuid' => $notification->uuid,
            'channel' => $notification->channel,
            'level' => $notification->level,
            'title' => $notification->title,
            'message' => $notification->message,
            'action_url' => $notification->action_url,
            'metadata' => $notification->metadata,
            'deliveries' => $notification->deliveries
                ->map(fn ($delivery): array => $this->transformDelivery($delivery))
                ->values()
                ->all(),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    protected function transformDelivery(CoreNotificationDelivery $delivery): array
    {
        return [
            'id' => $delivery->id,
            'notification_uuid' => $delivery->notification?->uuid,
            'channel' => $delivery->channel,
            'status' => $delivery->status,
            'destination' => $delivery->destination,
            'status_detail' => $delivery->status_detail,
            'title' => $delivery->notification?->title ?? data_get($delivery->metadata, 'title'),
            'message' => $delivery->notification?->message ?? data_get($delivery->metadata, 'message'),
            'level' => $delivery->notification?->level ?? data_get($delivery->metadata, 'level'),
            'action_url' => $delivery->notification?->action_url ?? data_get($delivery->metadata, 'action_url'),
            'source' => data_get($delivery->metadata, 'source'),
            'queued' => (bool) data_get($delivery->metadata, 'queued', false),
            'attempts' => (int) data_get($delivery->metadata, 'attempts', 0),
            'max_attempts' => (int) data_get($delivery->metadata, 'max_attempts', 0),
            'backoff_schedule' => data_get($delivery->metadata, 'backoff_schedule', []),
            'last_attempt_at' => data_get($delivery->metadata, 'last_attempt_at'),
            'can_retry' => (bool) data_get($delivery->metadata, 'retriable', false),
            'mailer' => data_get($delivery->metadata, 'mailer'),
            'processed_at' => $delivery->processed_at?->toIso8601String(),
            'created_at' => $delivery->created_at?->toIso8601String(),
            'metadata' => $delivery->metadata ?? [],
        ];
    }
}
