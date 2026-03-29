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
            'mailer' => data_get($delivery->metadata, 'mailer'),
            'processed_at' => $delivery->processed_at?->toIso8601String(),
            'created_at' => $delivery->created_at?->toIso8601String(),
            'metadata' => $delivery->metadata ?? [],
        ];
    }
}
