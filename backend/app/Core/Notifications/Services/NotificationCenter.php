<?php

namespace App\Core\Notifications\Services;

use App\Core\Notifications\Models\CoreNotificationDelivery;
use App\Core\Settings\CoreSettingsManager;
use App\Core\Notifications\Models\CoreNotification;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationCenter
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected CoreSettingsManager $settings,
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

    public function createMultichannel(
        User $recipient,
        string $title,
        string $message,
        string $level = 'info',
        ?User $creator = null,
        ?string $actionUrl = null,
        array $metadata = [],
        array $channels = ['internal'],
    ): array {
        $organizationId = $this->tenantContext->organizationId($recipient);
        $normalizedChannels = collect($channels)
            ->filter(fn (mixed $channel): bool => is_string($channel) && $channel !== '')
            ->map(fn (string $channel): string => strtolower(trim($channel)))
            ->intersect(['internal', 'email', 'whatsapp', 'push'])
            ->values();

        if ($normalizedChannels->isEmpty()) {
            $normalizedChannels = collect(['internal']);
        }

        $featureFlags = $this->settings->resolveValues('global');
        $userPreferences = $this->settings->resolveValues('user', null, $recipient->id);
        $notification = null;
        $deliveries = collect();

        foreach ($normalizedChannels as $channel) {
            if ($channel === 'internal') {
                if (! ($userPreferences['notifications_internal'] ?? true)) {
                    $deliveries->push($this->logDelivery(
                        recipient: $recipient,
                        notification: null,
                        channel: $channel,
                        organizationId: $organizationId,
                        status: 'skipped_preference',
                        detail: 'El usuario deshabilito notificaciones internas.',
                        metadata: $metadata,
                    ));
                    continue;
                }

                $notification ??= $this->createInternal(
                    recipient: $recipient,
                    title: $title,
                    message: $message,
                    level: $level,
                    creator: $creator,
                    actionUrl: $actionUrl,
                    metadata: array_merge($metadata, [
                        'channels' => $normalizedChannels->all(),
                    ]),
                );

                $deliveries->push($this->logDelivery(
                    recipient: $recipient,
                    notification: $notification,
                    channel: $channel,
                    organizationId: $organizationId,
                    status: 'delivered',
                    detail: 'Entregada en bandeja interna.',
                    metadata: $metadata,
                    processedAt: now(),
                ));

                continue;
            }

            [$status, $detail] = $this->resolveExternalChannelStatus($channel, $featureFlags, $userPreferences);

            $deliveries->push($this->logDelivery(
                recipient: $recipient,
                notification: $notification,
                channel: $channel,
                organizationId: $organizationId,
                status: $status,
                detail: $detail,
                destination: $channel === 'email' ? $recipient->email : null,
                metadata: $metadata,
                processedAt: now(),
            ));
        }

        return [
            'notification' => $notification?->fresh(['deliveries']),
            'deliveries' => $deliveries,
        ];
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

    protected function resolveExternalChannelStatus(string $channel, array $featureFlags, array $userPreferences): array
    {
        $featureKey = 'feature_notifications_'.$channel;
        $preferenceKey = 'notifications_'.$channel;

        if (! ($featureFlags[$featureKey] ?? false)) {
            return ['skipped_disabled', 'Canal deshabilitado por feature flag global.'];
        }

        if (! ($userPreferences[$preferenceKey] ?? false)) {
            return ['skipped_preference', 'El usuario deshabilito este canal en sus preferencias.'];
        }

        return ['simulated', 'Canal listo para integracion externa.'];
    }

    protected function logDelivery(
        User $recipient,
        ?CoreNotification $notification,
        string $channel,
        ?int $organizationId,
        string $status,
        string $detail,
        array $metadata = [],
        ?string $destination = null,
        mixed $processedAt = null,
    ): CoreNotificationDelivery {
        return CoreNotificationDelivery::query()->create([
            'organizacion_id' => $organizationId,
            'notification_id' => $notification?->id,
            'recipient_id' => $recipient->id,
            'channel' => $channel,
            'status' => $status,
            'destination' => $destination,
            'status_detail' => $detail,
            'metadata' => $metadata,
            'processed_at' => $processedAt,
        ]);
    }
}
