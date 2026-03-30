<?php

namespace Tests\Feature\Api\V1;

use App\Core\Auth\Services\AccessTokenService;
use App\Models\Organizacion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_create_and_update_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $organizacion = Organizacion::query()->create([
            'nombre' => 'Tenant Roles',
            'slug' => 'tenant-roles',
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@stackbase.local',
            'organizacion_activa_id' => $organizacion->id,
        ]);
        $admin->organizaciones()->attach($organizacion->id);
        $admin->assignRole('admin');

        $token = app(AccessTokenService::class)->createForUser($admin, 'phpunit-admin');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonFragment([
                'available_permissions' => [
                    'api-tokens.manage',
                    'data-engine.access',
                    'demo.access',
                    'error-logs.view',
                    'integrations.manage',
                    'metrics.view',
                    'modules.manage',
                    'operations.view',
                    'roles.manage',
                    'security.manage',
                    'security.logs.view',
                    'settings.manage',
                    'tenancy.manage',
                    'technical.docs.view',
                    'users.impersonate',
                    'users.manage_roles',
                ],
            ]);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/roles', [
                'name' => 'operaciones',
                'permissions' => ['security.manage', 'roles.manage'],
            ])
            ->assertOk()
            ->assertJsonPath('datos.name', 'operaciones');

        $roleId = $createResponse->json('datos.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/roles/'.$roleId, [
                'name' => 'operaciones-regional',
                'permissions' => ['security.manage'],
            ])
            ->assertOk()
            ->assertJsonPath('datos.name', 'operaciones-regional')
            ->assertJsonMissing([
                'roles.manage',
            ]);
    }
}
