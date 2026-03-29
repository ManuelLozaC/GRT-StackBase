<?php

namespace App\Core\Notifications\Services;

use App\Core\Notifications\Mail\CoreNotificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailNotificationService
{
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
                'metadata' => [],
            ];
        }

        if (! $this->isConfigured()) {
            return [
                'status' => 'simulated',
                'detail' => 'Canal email listo para integracion, pero sin credenciales completas.',
                'destination' => $recipient->email,
                'metadata' => [],
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
                    'error' => $exception->getMessage(),
                ],
            ];
        }
    }
}
