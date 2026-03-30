<?php

namespace App\Core\Notifications\Services;

use App\Core\Notifications\Models\CorePushSubscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class FirebasePushService
{
    public function __construct(
        protected HttpFactory $http,
    ) {
    }

    public function isConfigured(): bool
    {
        $credentials = $this->credentials();

        return filled($credentials['project_id'] ?? null)
            && filled($credentials['client_email'] ?? null)
            && filled($credentials['private_key'] ?? null);
    }

    public function register(User $user, array $payload): CorePushSubscription
    {
        return CorePushSubscription::query()->updateOrCreate(
            ['token' => $payload['token']],
            [
                'organizacion_id' => $user->activeCompanyId(),
                'user_id' => $user->id,
                'device_name' => $payload['device_name'] ?? null,
                'platform' => $payload['platform'] ?? null,
                'browser' => $payload['browser'] ?? null,
                'endpoint' => $payload['endpoint'] ?? null,
                'is_active' => true,
                'last_used_at' => now(),
                'metadata' => array_filter([
                    'subscription' => $payload['subscription'] ?? null,
                    'user_agent' => request()->userAgent(),
                ]),
            ],
        );
    }

    public function deactivate(User $user, string $token): void
    {
        CorePushSubscription::query()
            ->where('user_id', $user->id)
            ->where('token', $token)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function subscriptionsFor(User $user): array
    {
        return CorePushSubscription::query()
            ->where('user_id', $user->id)
            ->latest('last_used_at')
            ->get()
            ->map(fn (CorePushSubscription $subscription): array => [
                'id' => $subscription->id,
                'device_name' => $subscription->device_name,
                'platform' => $subscription->platform,
                'browser' => $subscription->browser,
                'is_active' => $subscription->is_active,
                'last_used_at' => $subscription->last_used_at?->toIso8601String(),
                'created_at' => $subscription->created_at?->toIso8601String(),
            ])
            ->all();
    }

    public function sendToUser(User $recipient, string $title, string $message, ?string $actionUrl = null, array $metadata = []): array
    {
        $subscriptions = CorePushSubscription::query()
            ->where('user_id', $recipient->id)
            ->where('is_active', true)
            ->get();

        if ($subscriptions->isEmpty()) {
            return [
                'status' => 'skipped_missing_target',
                'detail' => 'El usuario no tiene dispositivos push registrados.',
                'destination' => null,
                'metadata' => [
                    'provider' => 'fcm',
                    'provider_status' => 'missing_target',
                    'error_code' => 'missing_target',
                    'subscriptions' => 0,
                ],
            ];
        }

        if (! $this->isConfigured()) {
            return [
                'status' => 'simulated',
                'detail' => 'FCM aun no esta configurado completamente en servidor.',
                'destination' => $subscriptions->count().' dispositivo(s)',
                'metadata' => [
                    'provider' => 'fcm',
                    'provider_status' => 'configuration_missing',
                    'error_code' => 'configuration_missing',
                    'subscriptions' => $subscriptions->count(),
                ],
            ];
        }

        $results = [];
        $deliveredCount = 0;
        $failedCount = 0;
        $invalidatedCount = 0;

        foreach ($subscriptions as $subscription) {
            $result = $this->sendToToken(
                token: $subscription->token,
                title: $title,
                message: $message,
                actionUrl: $actionUrl,
                metadata: $metadata,
            );

            $results[] = array_merge($result, [
                'subscription_id' => $subscription->id,
                'device_name' => $subscription->device_name,
                'browser' => $subscription->browser,
                'platform' => $subscription->platform,
            ]);

            if (($result['status'] ?? null) === 'delivered') {
                $deliveredCount++;
                $subscription->forceFill([
                    'last_used_at' => now(),
                ])->save();
                continue;
            }

            $failedCount++;

            if (($result['error_code'] ?? null) === 'UNREGISTERED') {
                $subscription->forceFill([
                    'is_active' => false,
                ])->save();
                $invalidatedCount++;
            }
        }

        $status = $deliveredCount > 0
            ? ($failedCount > 0 ? 'partial' : 'delivered')
            : 'failed';

        return [
            'status' => $status,
            'detail' => $deliveredCount > 0
                ? ($failedCount > 0
                    ? 'FCM entrego la notificacion parcialmente; algunos dispositivos fallaron.'
                    : 'FCM envio la notificacion a uno o mas dispositivos.')
                : 'FCM no pudo entregar la notificacion a los dispositivos registrados.',
            'destination' => $subscriptions->count().' dispositivo(s)',
            'metadata' => [
                'provider' => 'fcm',
                'provider_status' => $status,
                'subscriptions' => $subscriptions->count(),
                'delivered_count' => $deliveredCount,
                'failed_count' => $failedCount,
                'invalidated_subscriptions' => $invalidatedCount,
                'results' => $results,
            ],
        ];
    }

    protected function sendToToken(string $token, string $title, string $message, ?string $actionUrl = null, array $metadata = []): array
    {
        $credentials = $this->credentials();
        $webpush = [
            'notification' => [
                'title' => $title,
                'body' => $message,
            ],
        ];

        if (is_string($actionUrl) && preg_match('/^https?:\/\//i', $actionUrl) === 1) {
            $webpush['fcm_options'] = [
                'link' => $actionUrl,
            ];
        }

        $response = $this->http
            ->withToken($this->accessToken())
            ->acceptJson()
            ->post(
                sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $credentials['project_id']),
                [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $message,
                        ],
                        'data' => array_filter([
                            'title' => $title,
                            'message' => $message,
                            'action_url' => $actionUrl,
                            'payload' => json_encode($metadata),
                        ], fn ($value) => $value !== null && $value !== false),
                        'webpush' => $webpush,
                    ],
                ],
            );

        if ($response->successful()) {
            return [
                'status' => 'delivered',
                'message_id' => $response->json('name'),
            ];
        }

        $error = $response->json('error', []);

        return [
            'status' => 'failed',
            'http_status' => $response->status(),
            'error_code' => data_get($error, 'details.0.errorCode') ?? data_get($error, 'status'),
            'error_message' => data_get($error, 'message') ?? Str::limit($response->body(), 500),
        ];
    }

    protected function accessToken(): string
    {
        $credentials = $this->credentials();
        $cacheKey = 'firebase_access_token_'.md5($credentials['client_email'].$credentials['project_id']);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials): string {
            $now = CarbonImmutable::now();
            $header = $this->base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ]));
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $credentials['token_uri'],
                'exp' => $now->addHour()->timestamp,
                'iat' => $now->timestamp,
            ]));
            $unsignedToken = $header.'.'.$claims;
            $signature = '';

            if (! openssl_sign($unsignedToken, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('No se pudo firmar el token JWT para Firebase.');
            }

            $assertion = $unsignedToken.'.'.$this->base64UrlEncode($signature);
            $response = $this->http
                ->asForm()
                ->post($credentials['token_uri'], [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('No se pudo obtener access token de Firebase.');
            }

            return (string) $response->json('access_token');
        });
    }

    protected function credentials(): array
    {
        static $credentials = null;

        if ($credentials !== null) {
            return $credentials;
        }

        $path = config('firebase.credentials_path');

        if (is_string($path) && $path !== '' && File::exists($path)) {
            $credentials = json_decode((string) File::get($path), true) ?: [];

            return $credentials;
        }

        return $credentials = [
            'project_id' => config('firebase.project_id'),
            'client_email' => config('firebase.client_email'),
            'private_key' => config('firebase.private_key'),
            'token_uri' => config('firebase.token_uri'),
        ];
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
