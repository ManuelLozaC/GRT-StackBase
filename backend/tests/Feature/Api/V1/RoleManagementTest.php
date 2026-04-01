<?php

namespace Tests\Feature\Api\V1;

use App\Core\Audit\Models\AuditLog;
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
            ->assertJsonPath('meta.available_permissions.0', 'api-tokens.manage')
            ->assertJsonFragment(['integrations.view'])
            ->assertJsonFragment(['integrations.test'])
            ->assertJsonFragment(['modules.view'])
            ->assertJsonFragment(['roles.view'])
            ->assertJsonFragment(['settings.view'])
            ->assertJsonFragment(['users.view'])
            ->assertJsonFragment(['users.roles.manage']);

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

        $auditLog = AuditLog::query()
            ->where('event_key', 'role.updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertSame(['roles.manage'], $auditLog->context['removed_permissions']);
        $this->assertSame([], $auditLog->context['added_permissions']);
    }
}
