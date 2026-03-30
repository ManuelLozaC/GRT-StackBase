<?php

namespace Tests\Feature\Api\V1\Demo;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Tenancy\TenantContext;
use App\Models\Organizacion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DemoAuditFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_toggle_is_audited(): void
    {
        $permission = Permission::query()->firstOrCreate([
            'name' => 'modules.manage',
            'guard_name' => 'web',
        ]);

        [$user, $token] = $this->authenticateUser();
        $user->givePermissionTo($permission);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/modules/demo-platform', [
                'enabled' => true,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/audit')
            ->assertOk()
            ->assertJsonFragment([
                'event_key' => 'module.status.updated',
                'entity_key' => 'demo-platform',
            ]);
    }

    public function test_file_and_job_actions_are_audited(): void
    {
        Storage::fake('local');
        Queue::fake();
        config(['filesystems.default' => 'local']);

        [$user, $token] = $this->authenticateUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/demo/files', [
                'file' => UploadedFile::fake()->create('audit-demo.txt', 10, 'text/plain'),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/jobs', [
                'message' => 'auditar evento demo',
                'mode' => 'immediate',
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/audit')
            ->assertOk()
            ->assertJsonFragment([
                'event_key' => 'demo.file.uploaded',
            ])
            ->assertJsonFragment([
                'event_key' => 'demo.job.dispatched',
            ])
            ->assertJsonFragment([
                'event_key' => 'demo.job.completed',
            ]);
    }

    public function test_audit_log_is_scoped_by_active_organization(): void
    {
        [$user, $token, $primaryOrganization, $secondaryOrganization] = $this->authenticateUserWithTwoOrganizations();

        $user->forceFill([
            'organizacion_activa_id' => $primaryOrganization->id,
        ])->save();

        app(AuditLogger::class)->record(
            eventKey: 'demo.audit.primary',
            actor: $user->fresh(),
            entityType: 'demo',
            entityKey: 'primary',
            summary: 'Evento de auditoria para tenant primario',
            sourceModule: 'demo-platform',
        );

        $user->forceFill([
            'organizacion_activa_id' => $secondaryOrganization->id,
        ])->save();

        app(AuditLogger::class)->record(
            eventKey: 'demo.audit.secondary',
            actor: $user->fresh(),
            entityType: 'demo',
            entityKey: 'secondary',
            summary: 'Evento de auditoria para tenant secundario',
            sourceModule: 'demo-platform',
        );

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $primaryOrganization->id,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/audit')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'event_key' => 'demo.audit.primary',
            ])
            ->assertJsonMissing([
                'event_key' => 'demo.audit.secondary',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $secondaryOrganization->id,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/audit')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'event_key' => 'demo.audit.secondary',
            ])
            ->assertJsonMissing([
                'event_key' => 'demo.audit.primary',
            ]);
    }

    public function test_audit_logger_uses_tenant_context_when_actor_is_not_passed(): void
    {
        [$user] = $this->authenticateUser();

        $tenantContext = app(TenantContext::class);
        $tenantContext->setFromUser($user);

        try {
            app(AuditLogger::class)->record(
                eventKey: 'demo.audit.contextual',
                entityType: 'demo',
                entityKey: 'contextual',
                summary: 'Evento heredado desde tenant context',
                sourceModule: 'demo-platform',
            );
        } finally {
            $tenantContext->clear();
        }

        $this->assertDatabaseHas('core_audit_logs', [
            'event_key' => 'demo.audit.contextual',
            'organizacion_id' => $user->organizacion_activa_id,
            'actor_id' => $user->id,
        ]);
    }

    protected function authenticateUser(): array
    {
        $this->seed(RolePermissionSeeder::class);

        $organizacion = Organizacion::query()->create([
            'nombre' => 'Acme Audit',
            'slug' => 'acme-audit',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $organizacion->id,
        ]);

        $user->organizaciones()->attach($organizacion->id);
        $user->givePermissionTo('demo.access');

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
        $this->seed(RolePermissionSeeder::class);

        $primaryOrganization = Organizacion::query()->create([
            'nombre' => 'Acme Audit Primary',
            'slug' => 'acme-audit-primary',
        ]);

        $secondaryOrganization = Organizacion::query()->create([
            'nombre' => 'Acme Audit Secondary',
            'slug' => 'acme-audit-secondary',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $primaryOrganization->id,
        ]);

        $user->organizaciones()->attach([
            $primaryOrganization->id,
            $secondaryOrganization->id,
        ]);
        $user->givePermissionTo('demo.access');

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
