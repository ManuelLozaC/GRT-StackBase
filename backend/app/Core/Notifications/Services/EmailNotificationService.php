<?php

namespace App\Core\Notifications\Services;

use App\Core\Notifications\Mail\CoreNotificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailNotificationService
{
    public function providerName(): string
    {
        return (string) config('mail.default', 'mail');
    }

    public function isConfigured(): bool
    {
        return filled(config('mail.from.address'))
            && match (config('mail.default')) {
                'resend' => filled(config('services.resend.key')),
                'smtp' => filled(config('mail.mailers.smtp.host')) && filled(config('mail.mailers.smtp.username')) && filled(config('mail.mailers.smtp.password')),
                default => true,
            };
    }

    public function canSendToUser(User $recipient): bool
    {
        return filled($recipient->email);
    }

    public function sendNow(User $recipient, string $title, string $message, ?string $actionUrl = null, array $metadata = []): array
    {
        if (! filled($recipient->email)) {
            return [
                'status' => 'skipped_missing_target',
                'detail' => 'El usuario no tiene correo electronico configurado.',
                'destination' => null,
                'metadata' => [
                    'provider' => $this->providerName(),
                    'provider_status' => 'missing_target',
                    'error_code' => 'missing_target',
                ],
            ];
        }

        if (! $this->isConfigured()) {
            return [
                'status' => 'simulated',
                'detail' => 'Canal email listo para integracion, pero sin credenciales completas.',
                'destination' => $recipient->email,
                'metadata' => [
                    'provider' => $this->providerName(),
                    'provider_status' => 'configuration_missing',
                    'error_code' => 'configuration_missing',
                ],
            ];
        }

        try {
            Mail::to($recipient->email)->send(new CoreNotificationMail(
                title: $title,
                messageBody: $message,
                actionUrl: $actionUrl,
                payloadContext: $metadata,
            ));

            return [
                'status' => 'delivered',
                'detail' => 'Correo enviado correctamente por el proveedor configurado.',
                'destination' => $recipient->email,
                'metadata' => [
                    'mailer' => config('mail.default'),
                    'provider' => $this->providerName(),
                    'provider_status' => 'accepted',
                ],
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'status' => 'failed',
                'detail' => 'El proveedor de correo rechazo o no pudo procesar el envio.',
                'destination' => $recipient->email,
                'metadata' => [
                    'mailer' => config('mail.default'),
                    'provider' => $this->providerName(),
                    'provider_status' => 'rejected',
                    'error_code' => 'provider_rejected',
                    'error' => $exception->getMessage(),
                ],
            ];
        }
    }
}
