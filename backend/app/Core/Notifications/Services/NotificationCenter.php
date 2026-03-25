<?php

namespace App\Core\Notifications\Services;

use App\Core\Notifications\Models\CoreNotification;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Support\Str;

class NotificationCenter
{
    public function __construct(
        protected TenantContext $tenantContext,
    ) {
    }

    public function createInternal(
        User $recipient,
        string $title,
        string $message,
        string $level = 'info',
        ?User $creator = null,
        ?string $actionUrl = null,
        array $metadata = [],
    ): CoreNotification {
        return CoreNotification::query()->create([
            'uuid' => (string) Str::uuid(),
            'organizacion_id' => $this->tenantContext->organizationId($recipient),
            'recipient_id' => $recipient->id,
            'created_by' => $creator?->id,
            'channel' => 'internal',
            'level' => $level,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'metadata' => $metadata,
        ]);
    }

    public function markAsRead(CoreNotification $notification): CoreNotification
    {
        if ($notification->read_at === null) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return $notification->fresh();
    }

    public function markAllAsReadFor(User $user): int
    {
        return CoreNotification::query()
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
