<?php

namespace App\Jobs\Notifications;

use App\Core\Notifications\Models\CoreNotificationDelivery;
use App\Core\Notifications\Services\EmailNotificationService;
use App\Core\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEmailNotificationDelivery implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $deliveryId,
        public string $title,
        public string $message,
        public ?string $actionUrl = null,
        public array $metadata = [],
    ) {
        $this->onQueue('notifications');
    }

    public function handle(EmailNotificationService $emailService, TenantContext $tenantContext): void
    {
        $delivery = CoreNotificationDelivery::query()
            ->with('recipient')
            ->findOrFail($this->deliveryId);

        if ($delivery->recipient === null) {
            $delivery->forceFill([
                'status' => 'failed',
                'status_detail' => 'La entrega no tiene destinatario asociado.',
                'processed_at' => now(),
                'metadata' => array_merge($delivery->metadata ?? [], [
                    'queued' => true,
                    'error' => 'missing_recipient',
                ]),
            ])->save();

            return;
        }

        $tenantContext->setOrganizationId($delivery->organizacion_id);

        try {
            $result = $emailService->sendNow(
                recipient: $delivery->recipient,
                title: $this->title,
                message: $this->message,
                actionUrl: $this->actionUrl,
                metadata: $this->metadata,
            );

            $delivery->forceFill([
                'status' => $result['status'],
                'destination' => $result['destination'] ?? $delivery->destination,
                'status_detail' => $result['detail'],
                'processed_at' => now(),
                'metadata' => array_merge($delivery->metadata ?? [], $result['metadata'] ?? []),
            ])->save();
        } finally {
            $tenantContext->clear();
        }
    }
}
