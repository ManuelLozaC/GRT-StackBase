<?php

namespace App\Http\Controllers\Api\V1\Demo;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Modules\ModuleSettingsManager;
use App\Core\Notifications\Services\NotificationCenter;
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
    ) {
    }

    public function store(StoreDemoNotificationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notification = $this->notifications->createInternal(
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
        );

        $this->auditLogger->record(
            eventKey: 'demo.notification.created',
            actor: $user,
            entityType: 'core_notification',
            entityKey: $notification->uuid,
            summary: 'Se genero una notificacion demo',
            sourceModule: 'demo-platform',
            context: [
                'level' => $notification->level,
                'title' => $notification->title,
            ],
        );

        return $this->successResponse(
            data: [
                'uuid' => $notification->uuid,
                'title' => $notification->title,
                'message' => $notification->message,
                'level' => $notification->level,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ],
            message: 'Notificacion demo creada',
        );
    }
}
