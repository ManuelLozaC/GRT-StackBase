<?php

namespace Tests\Feature\Api\V1;

use App\Core\Notifications\Models\CorePushSubscription;
use App\Core\Settings\CoreSettingsManager;
use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReleaseSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthcheck_exposes_operational_checks(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('datos.checks.database.status', 'ok')
            ->assertJsonStructure([
                'datos' => [
                    'checks' => [
                        'database',
                        'redis',
                        'mail',
                        'queue',
                        'storage',
                    ],
                ],
            ]);
    }

    public function test_auth_smoke_login_and_me(): void
    {
        [$user, $token] = $this->authenticateUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('datos.email', $user->email)
            ->assertJsonPath('datos.organizacion_activa.nombre', 'Smoke Org');
    }

    public function test_data_engine_smoke_lists_resources(): void
    {
        [, $token] = $this->authenticateUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/resources')
            ->assertOk()
            ->assertJsonStructure([
                'datos',
            ]);
    }

    public function test_push_smoke_lists_registered_devices(): void
    {
        [$user, $token] = $this->authenticateUser();

        CorePushSubscription::query()->create([
            'organizacion_id' => $user->organizacion_activa_id,
            'user_id' => $user->id,
            'token' => 'smoke-token-1',
            'device_name' => 'Smoke Device',
            'platform' => 'Win32',
            'browser' => 'Smoke Browser',
            'endpoint' => 'http://localhost/smoke',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/notifications/push-subscriptions')
            ->assertOk()
            ->assertJsonPath('datos.subscriptions.0.device_name', 'Smoke Device');
    }

    public function test_email_smoke_queues_delivery(): void
    {
        [$user, $token] = $this->authenticateUser();
        Mail::fake();
        Queue::fake();

        app(CoreSettingsManager::class)->update('global', [
            'feature_notifications_email' => true,
        ]);

        app(CoreSettingsManager::class)->update('user', [
            'notifications_email' => true,
        ], null, $user->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/notifications', [
                'title' => 'Smoke Email',
                'message' => 'Smoke email body',
                'channels' => ['email'],
            ])
            ->assertOk()
            ->assertJsonPath('datos.deliveries.0.status', 'queued');
    }

    protected function authenticateUser(): array
    {
        $organizacion = Organizacion::query()->create([
            'nombre' => 'Smoke Org',
            'slug' => 'smoke-org',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $organizacion->id,
        ]);

        $user->organizaciones()->attach($organizacion->id);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        return [
            $user,
            $loginResponse->json('datos.token'),
        ];
    }
}
