<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Notifications\Models\CoreNotification;
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
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notifications = CoreNotification::query()
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
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
