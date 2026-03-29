<?php

namespace App\Core\Notifications\Services;

use App\Jobs\Notifications\ProcessEmailNotificationDelivery;
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
        protected EmailNotificationService $emailService,
        protected FirebasePushService $pushService,
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
        $deliveryMetadata = array_merge($metadata, [
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
        ]);
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
                        metadata: $deliveryMetadata,
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
                    metadata: $deliveryMetadata,
                    processedAt: now(),
                ));

                continue;
            }

            [$status, $detail, $destination, $channelMetadata] = $this->resolveExternalChannelStatus(
                channel: $channel,
                recipient: $recipient,
                title: $title,
                message: $message,
                actionUrl: $actionUrl,
                featureFlags: $featureFlags,
                userPreferences: $userPreferences,
                metadata: $deliveryMetadata,
            );

            $delivery = $this->logDelivery(
                recipient: $recipient,
                notification: $notification,
                channel: $channel,
                organizationId: $organizationId,
                status: $status,
                detail: $detail,
                destination: $destination,
                metadata: array_merge($deliveryMetadata, $channelMetadata),
                processedAt: now(),
            );

            if ($channel === 'email' && $status === 'queued') {
                ProcessEmailNotificationDelivery::dispatch(
                    deliveryId: $delivery->id,
                    title: $title,
                    message: $message,
                    actionUrl: $actionUrl,
                    metadata: $deliveryMetadata,
                );
            }

            $deliveries->push($delivery);
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

    protected function resolveExternalChannelStatus(
        string $channel,
        User $recipient,
        string $title,
        string $message,
        ?string $actionUrl,
        array $featureFlags,
        array $userPreferences,
        array $metadata = [],
    ): array
    {
        $featureKey = 'feature_notifications_'.$channel;
        $preferenceKey = 'notifications_'.$channel;

        if (! ($featureFlags[$featureKey] ?? false)) {
            return ['skipped_disabled', 'Canal deshabilitado por feature flag global.', null, []];
        }

        if (! ($userPreferences[$preferenceKey] ?? false)) {
            return ['skipped_preference', 'El usuario deshabilito este canal en sus preferencias.', null, []];
        }

        if ($channel === 'email') {
            if (! $this->emailService->canSendToUser($recipient)) {
                return ['skipped_missing_target', 'El usuario no tiene correo electronico configurado.', null, []];
            }

            if (! $this->emailService->isConfigured()) {
                return ['simulated', 'Canal email listo para integracion, pero sin credenciales completas.', $recipient->email, []];
            }

            return [
                'queued',
                'Entrega de correo encolada para procesamiento asincrono.',
                $recipient->email,
                [
                    'mailer' => config('mail.default'),
                    'queued' => true,
                ],
            ];
        }

        if ($channel === 'push') {
            $result = $this->pushService->sendToUser(
                recipient: $recipient,
                title: $title,
                message: $message,
                actionUrl: $actionUrl,
                metadata: $metadata,
            );

            return [
                $result['status'],
                $result['detail'],
                $result['destination'] ?? null,
                $result['metadata'] ?? [],
            ];
        }

        return ['simulated', 'Canal listo para integracion externa.', null, []];
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
