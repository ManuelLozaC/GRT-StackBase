<?php

namespace Tests\Feature\Api\V1;

use App\Core\Audit\Models\AuditLog;
use App\Core\Auth\Services\AccessTokenService;
use App\Models\Organizacion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_audit_logs_with_filters(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $organization = Organizacion::query()->create([
            'nombre' => 'Audit Admin Tenant',
            'slug' => 'audit-admin-tenant',
        ]);

        $admin = User::factory()->create([
            'email' => 'audit-admin@stackbase.local',
            'organizacion_activa_id' => $organization->id,
        ]);
        $admin->organizaciones()->attach($organization->id);
        $admin->assignRole('admin');

        AuditLog::query()->create([
            'organizacion_id' => $organization->id,
            'actor_id' => $admin->id,
            'event_key' => 'users.created',
            'entity_type' => 'user',
            'entity_key' => '25',
            'source_module' => 'core-platform',
            'summary' => 'Usuario creado',
            'context' => ['source' => 'phpunit', 'request_id' => 'req-audit-001'],
            'occurred_at' => now()->subMinutes(5),
        ]);

        AuditLog::query()->create([
            'organizacion_id' => $organization->id,
            'actor_id' => $admin->id,
            'event_key' => 'modules.enabled',
            'entity_type' => 'module',
            'entity_key' => 'demo-platform',
            'source_module' => 'core-platform',
            'summary' => 'Modulo habilitado',
            'context' => ['source' => 'phpunit', 'request_id' => 'req-audit-002'],
            'occurred_at' => now()->subMinutes(3),
        ]);

        $token = app(AccessTokenService::class)->createForUser($admin, 'phpunit-audit-admin');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/audit/logs?entity_type=user')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.event_key', 'users.created')
            ->assertJsonPath('datos.0.actor.email', 'audit-admin@stackbase.local')
            ->assertJsonPath('datos.0.request_id', 'req-audit-001');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/audit/logs?request_id=req-audit-002')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.event_key', 'modules.enabled');
    }
}
