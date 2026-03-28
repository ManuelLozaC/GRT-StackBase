<?php

namespace Tests\Feature\Api\V1\Demo;

use App\Core\Modules\ModuleSettingsManager;
use App\Core\Notifications\Services\NotificationCenter;
use App\Core\Settings\CoreSettingsManager;
use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoNotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_demo_notifications(): void
    {
        [$user, $token] = $this->authenticateUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/notifications', [
                'title' => 'Nueva exportacion lista',
                'message' => 'La exportacion de demo termino correctamente.',
                'level' => 'success',
            ])
            ->assertOk()
            ->assertJsonPath('datos.level', 'success');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.unread_count', 1)
            ->assertJsonFragment([
                'title' => 'Nueva exportacion lista',
                'level' => 'success',
            ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        [$user, $token] = $this->authenticateUser();
        $notification = app(NotificationCenter::class)->createInternal(
            recipient: $user,
            title: 'Demo pendiente',
            message: 'Marca esta notificacion como leida.',
        );

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/notifications/'.$notification->uuid.'/read')
            ->assertOk()
            ->assertJsonPath('datos.read_at', fn ($value) => ! empty($value));
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        [$user, $token] = $this->authenticateUser();

        app(NotificationCenter::class)->createInternal(
            recipient: $user,
            title: 'Demo 1',
            message: 'Primera notificacion.',
        );

        app(NotificationCenter::class)->createInternal(
            recipient: $user,
            title: 'Demo 2',
            message: 'Segunda notificacion.',
        );

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('datos.updated_count', 2);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 0);
    }

    public function test_notifications_are_scoped_by_active_organization(): void
    {
        [$user, $token, $primaryOrganization, $secondaryOrganization] = $this->authenticateUserWithTwoOrganizations();

        $user->forceFill([
            'organizacion_activa_id' => $primaryOrganization->id,
        ])->save();

        app(NotificationCenter::class)->createInternal(
            recipient: $user->fresh(),
            title: 'Primary notification',
            message: 'Pertenece a la organizacion primaria.',
        );

        $user->forceFill([
            'organizacion_activa_id' => $secondaryOrganization->id,
        ])->save();

        app(NotificationCenter::class)->createInternal(
            recipient: $user->fresh(),
            title: 'Secondary notification',
            message: 'Pertenece a la organizacion secundaria.',
        );

        $user->forceFill([
            'organizacion_activa_id' => $primaryOrganization->id,
        ])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'title' => 'Primary notification',
            ])
            ->assertJsonMissing([
                'title' => 'Secondary notification',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $secondaryOrganization->id,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'title' => 'Secondary notification',
            ])
            ->assertJsonMissing([
                'title' => 'Primary notification',
            ]);
    }

    public function test_demo_notification_uses_module_default_level_setting(): void
    {
        [$user, $token] = $this->authenticateUser();
        app(ModuleSettingsManager::class)->update('demo-platform', [
            'notification_default_level' => 'warning',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/notifications', [
                'title' => 'Sin nivel explicito',
                'message' => 'Debe usar setting por defecto.',
            ])
            ->assertOk()
            ->assertJsonPath('datos.level', 'warning');
    }

    public function test_demo_notification_logs_multichannel_deliveries(): void
    {
        [$user, $token] = $this->authenticateUser();

        app(CoreSettingsManager::class)->update('global', [
            'feature_notifications_email' => true,
            'feature_notifications_push' => true,
        ]);

        app(CoreSettingsManager::class)->update('user', [
            'notifications_email' => true,
            'notifications_push' => false,
        ], null, $user->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/notifications', [
                'title' => 'Canales demo',
                'message' => 'Debe registrar entregas por canal.',
                'channels' => ['internal', 'email', 'push'],
            ])
            ->assertOk()
            ->assertJsonPath('datos.deliveries.0.channel', 'internal')
            ->assertJsonPath('datos.deliveries.0.status', 'delivered')
            ->assertJsonPath('datos.deliveries.1.channel', 'email')
            ->assertJsonPath('datos.deliveries.1.status', 'simulated')
            ->assertJsonPath('datos.deliveries.2.channel', 'push')
            ->assertJsonPath('datos.deliveries.2.status', 'skipped_preference');

        $this->assertDatabaseHas('core_notification_deliveries', [
            'channel' => 'email',
            'status' => 'simulated',
            'recipient_id' => $user->id,
        ]);
    }

    protected function authenticateUser(): array
    {
        $organizacion = Organizacion::query()->create([
            'nombre' => 'Acme Notifications',
            'slug' => 'acme-notifications',
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

    protected function authenticateUserWithTwoOrganizations(): array
    {
        $primaryOrganization = Organizacion::query()->create([
            'nombre' => 'Acme Notifications Primary',
            'slug' => 'acme-notifications-primary',
        ]);

        $secondaryOrganization = Organizacion::query()->create([
            'nombre' => 'Acme Notifications Secondary',
            'slug' => 'acme-notifications-secondary',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $primaryOrganization->id,
        ]);

        $user->organizaciones()->attach([
            $primaryOrganization->id,
            $secondaryOrganization->id,
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        return [
            $user,
            $loginResponse->json('datos.token'),
            $primaryOrganization,
            $secondaryOrganization,
        ];
    }
}
