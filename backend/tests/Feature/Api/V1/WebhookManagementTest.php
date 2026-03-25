<?php

namespace Tests\Feature\Api\V1;

use App\Core\Auth\Services\AccessTokenService;
use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WebhookManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_list_and_test_webhook_endpoints(): void
    {
        [$user, $token] = $this->authenticateIntegrationAdmin();

        Http::fake([
            'https://hooks.stackbase.test/*' => Http::response([
                'received' => true,
            ], 202),
        ]);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/webhooks/endpoints', [
                'module_key' => 'demo-platform',
                'event_key' => 'demo.notification.created',
                'target_url' => 'https://hooks.stackbase.test/demo',
                'signing_secret' => 'super-secret-123',
                'custom_headers' => [
                    'X-Environment' => 'testing',
                ],
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('datos.module_key', 'demo-platform')
            ->assertJsonPath('datos.event_key', 'demo.notification.created')
            ->assertJsonPath('datos.is_active', true);

        $this->assertStringNotContainsString('super-secret-123', $createResponse->getContent());

        $endpointId = $createResponse->json('datos.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/webhooks/endpoints/'.$endpointId, [
                'module_key' => 'demo-platform',
                'event_key' => 'demo.notification.created',
                'target_url' => 'https://hooks.stackbase.test/demo-updated',
                'signing_secret' => '',
                'custom_headers' => [
                    'X-Environment' => 'staging',
                ],
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('datos.target_url', 'https://hooks.stackbase.test/demo-updated')
            ->assertJsonPath('datos.is_active', false);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/webhooks/endpoints')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'key' => 'demo-platform',
            ])
            ->assertJsonFragment([
                'key' => 'demo.notification.created',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/webhooks/endpoints/'.$endpointId, [
                'module_key' => 'demo-platform',
                'event_key' => 'demo.notification.created',
                'target_url' => 'https://hooks.stackbase.test/demo-updated',
                'custom_headers' => [
                    'X-Environment' => 'staging',
                ],
                'signing_secret' => 'super-secret-123',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('datos.is_active', true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/webhooks/endpoints/'.$endpointId.'/test', [
                'payload' => [
                    'mode' => 'phpunit',
                    'resource' => 'demo.notification',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('datos.status', 'succeeded')
            ->assertJsonPath('datos.response_status', 202);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/webhooks/deliveries')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.event_key', 'demo.notification.created');

        $this->assertDatabaseHas('core_webhook_endpoints', [
            'id' => $endpointId,
            'organizacion_id' => $user->organizacion_activa_id,
            'module_key' => 'demo-platform',
            'event_key' => 'demo.notification.created',
        ]);

        $this->assertDatabaseHas('core_webhook_deliveries', [
            'endpoint_id' => $endpointId,
            'module_key' => 'demo-platform',
            'event_key' => 'demo.notification.created',
            'status' => 'succeeded',
            'response_status' => 202,
        ]);
    }

    public function test_demo_events_dispatch_to_registered_webhook_endpoints(): void
    {
        [$user, $token] = $this->authenticateIntegrationAdmin();

        Http::fake([
            'https://hooks.stackbase.test/demo-live' => Http::response([
                'received' => true,
            ], 200),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/webhooks/endpoints', [
                'module_key' => 'demo-platform',
                'event_key' => 'demo.notification.created',
                'target_url' => 'https://hooks.stackbase.test/demo-live',
                'signing_secret' => 'super-secret-456',
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/notifications', [
                'title' => 'Webhook notification',
                'message' => 'Debe disparar una entrega real.',
                'channels' => ['internal'],
            ])
            ->assertOk();

        $this->assertDatabaseHas('core_webhook_deliveries', [
            'organizacion_id' => $user->organizacion_activa_id,
            'module_key' => 'demo-platform',
            'event_key' => 'demo.notification.created',
            'status' => 'succeeded',
            'response_status' => 200,
        ]);
    }

    public function test_webhook_endpoints_are_scoped_by_active_organization(): void
    {
        [$user, $token, $primaryOrganization, $secondaryOrganization] = $this->authenticateAdminWithTwoOrganizations();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/webhooks/endpoints', [
                'module_key' => 'core-platform',
                'event_key' => 'module.status.updated',
                'target_url' => 'https://hooks.stackbase.test/org-a',
                'signing_secret' => 'primary-secret-123',
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $secondaryOrganization->id,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/webhooks/endpoints', [
                'module_key' => 'core-platform',
                'event_key' => 'module.status.updated',
                'target_url' => 'https://hooks.stackbase.test/org-b',
                'signing_secret' => 'secondary-secret-123',
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/webhooks/endpoints')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.target_url', 'https://hooks.stackbase.test/org-b');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $primaryOrganization->id,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/webhooks/endpoints')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.target_url', 'https://hooks.stackbase.test/org-a');
    }

    protected function authenticateIntegrationAdmin(): array
    {
        $organization = Organizacion::query()->create([
            'nombre' => 'Webhook Tenant',
            'slug' => 'webhook-tenant',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $organization->id,
        ]);

        $user->organizaciones()->attach($organization->id);

        $permission = Permission::query()->firstOrCreate([
            'name' => 'integrations.manage',
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo($permission);

        return [
            $user,
            app(AccessTokenService::class)->createForUser($user, 'phpunit-webhooks'),
        ];
    }

    protected function authenticateAdminWithTwoOrganizations(): array
    {
        $primaryOrganization = Organizacion::query()->create([
            'nombre' => 'Webhook Primary',
            'slug' => 'webhook-primary',
        ]);
        $secondaryOrganization = Organizacion::query()->create([
            'nombre' => 'Webhook Secondary',
            'slug' => 'webhook-secondary',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $primaryOrganization->id,
        ]);

        $user->organizaciones()->attach([
            $primaryOrganization->id,
            $secondaryOrganization->id,
        ]);

        $permission = Permission::query()->firstOrCreate([
            'name' => 'integrations.manage',
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo($permission);

        return [
            $user,
            app(AccessTokenService::class)->createForUser($user, 'phpunit-webhooks-multi'),
            $primaryOrganization,
            $secondaryOrganization,
        ];
    }
}
