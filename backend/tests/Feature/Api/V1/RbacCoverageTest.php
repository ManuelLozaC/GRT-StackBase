<?php

namespace Tests\Feature\Api\V1;

use App\Core\Auth\Services\AccessTokenService;
use App\Models\Organizacion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_routes_require_demo_access_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$user, $token] = $this->issueToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/files')
            ->assertForbidden();

        $user->givePermissionTo('demo.access');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/files')
            ->assertOk();
    }

    public function test_operational_routes_now_use_granular_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$user, $token] = $this->issueToken();

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/operations/overview')->assertForbidden();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/metrics/overview')->assertForbidden();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/security/logs')->assertForbidden();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/error-logs')->assertForbidden();

        $user->givePermissionTo([
            'operations.view',
            'metrics.view',
            'security.logs.view',
            'error-logs.view',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/operations/overview')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/metrics/overview')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/security/logs')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/error-logs')->assertOk();
    }

    public function test_simple_user_cannot_access_demo_data_engine_documentation_or_api_tokens_without_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$user, $token] = $this->issueToken();

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/demo/files')->assertForbidden();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/data/resources')->assertForbidden();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/openapi.json')->assertForbidden();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/auth/api-tokens')->assertForbidden();

        $user->givePermissionTo([
            'demo.access',
            'data-engine.access',
            'technical.docs.view',
            'api-tokens.manage',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/demo/files')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/data/resources')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/openapi.json')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/auth/api-tokens')->assertOk();
    }

    public function test_admin_surfaces_support_view_permissions_without_manage_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$user, $token] = $this->issueToken();

        $user->givePermissionTo([
            'modules.view',
            'integrations.view',
            'settings.view',
            'users.view',
            'roles.view',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/modules')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/modules/core-platform/settings')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/webhooks/endpoints')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/settings/global')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/users')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/roles')->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)->patchJson('/api/v1/modules/demo-platform', ['enabled' => true])->assertForbidden();
        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/webhooks/endpoints', [])->assertForbidden();
        $this->withHeader('Authorization', 'Bearer '.$token)->patchJson('/api/v1/settings/global', [])->assertForbidden();
        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/users', [])->assertForbidden();
        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/roles', [])->assertForbidden();
    }

    protected function issueToken(): array
    {
        $organization = Organizacion::query()->create([
            'nombre' => 'Tenant RBAC',
            'slug' => 'tenant-rbac',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $organization->id,
        ]);
        $user->organizaciones()->attach($organization->id);

        return [
            $user,
            app(AccessTokenService::class)->createForUser($user, 'phpunit-rbac'),
        ];
    }
}
