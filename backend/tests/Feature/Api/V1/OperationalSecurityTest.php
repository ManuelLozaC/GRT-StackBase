<?php

namespace Tests\Feature\Api\V1;

use App\Core\Auth\Services\AccessTokenService;
use App\Models\Organizacion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_responses_include_request_id_and_header(): void
    {
        $response = $this->withHeader('X-Request-Id', 'req-test-123')
            ->getJson('/api/v1/health');

        $response->assertOk()
            ->assertHeader('X-Request-Id', 'req-test-123')
            ->assertHeader('X-Response-Time-ms')
            ->assertJsonPath('meta.request_id', 'req-test-123');
    }

    public function test_failed_login_is_logged_in_security_logs(): void
    {
        User::factory()->create([
            'email' => 'security@test.local',
            'password' => 'password',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.11'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'security@test.local',
                'password' => 'incorrecta',
            ])->assertStatus(422);

        $this->assertDatabaseHas('core_security_logs', [
            'event_key' => 'auth.login_failed',
            'severity' => 'warning',
        ]);
    }

    public function test_login_endpoint_is_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'ratelimit@test.local',
            'password' => 'password',
        ]);

        foreach (range(1, 10) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.12'])
                ->postJson('/api/v1/auth/login', [
                    'email' => 'ratelimit@test.local',
                    'password' => 'incorrecta',
                ])->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.12'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit@test.local',
                'password' => 'incorrecta',
            ])->assertStatus(429);
    }

    public function test_admin_can_list_security_logs(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $organization = Organizacion::query()->create([
            'nombre' => 'Security Tenant',
            'slug' => 'security-tenant',
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@stackbase.local',
            'organizacion_activa_id' => $organization->id,
        ]);
        $admin->organizaciones()->attach($organization->id);
        $admin->assignRole('admin');

        User::factory()->create([
            'email' => 'security.member@test.local',
            'password' => 'password',
            'organizacion_activa_id' => $organization->id,
        ])->organizaciones()->attach($organization->id);

        $token = app(AccessTokenService::class)->createForUser($admin, 'phpunit-security');

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.13'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'security.member@test.local',
                'password' => 'incorrecta',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/security/logs')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.event_key', 'auth.login_failed');
    }

    public function test_admin_can_view_operations_overview(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $organization = Organizacion::query()->create([
            'nombre' => 'Operations Tenant',
            'slug' => 'operations-tenant',
        ]);

        $admin = User::factory()->create([
            'email' => 'ops-admin@stackbase.local',
            'organizacion_activa_id' => $organization->id,
        ]);
        $admin->organizaciones()->attach($organization->id);
        $admin->assignRole('admin');

        \App\Core\Jobs\Models\CoreJobRun::query()->create([
            'uuid' => 'job-run-1',
            'organizacion_id' => $organization->id,
            'requested_by' => $admin->id,
            'job_key' => 'demo.job',
            'queue' => 'demo',
            'status' => 'failed',
            'requested_payload' => ['demo' => true],
            'attempts' => 1,
            'dispatched_at' => now()->subHour(),
            'failed_at' => now()->subMinutes(30),
            'error_message' => 'Job failed',
        ]);

        \App\Core\DataEngine\Models\CoreDataTransferRun::query()->create([
            'uuid' => 'transfer-run-1',
            'organizacion_id' => $organization->id,
            'requested_by' => $admin->id,
            'resource_key' => 'demo-contacts',
            'source_module' => 'demo-platform',
            'type' => 'export',
            'status' => 'failed',
            'records_total' => 10,
            'records_processed' => 4,
            'records_failed' => 6,
            'error_summary' => 'Transfer failed',
            'metadata' => ['format' => 'csv'],
            'finished_at' => now()->subMinutes(20),
        ]);

        \App\Core\Notifications\Models\CoreNotification::query()->create([
            'uuid' => 'notification-1',
            'organizacion_id' => $organization->id,
            'recipient_id' => $admin->id,
            'created_by' => $admin->id,
            'channel' => 'internal',
            'level' => 'info',
            'title' => 'Ops notification',
            'message' => 'Notification body',
            'metadata' => [],
        ]);

        \App\Core\Security\Models\CoreSecurityLog::query()->create([
            'organizacion_id' => $organization->id,
            'actor_id' => $admin->id,
            'event_key' => 'security.test_event',
            'severity' => 'warning',
            'summary' => 'Security warning',
            'context' => ['source' => 'test'],
            'occurred_at' => now()->subMinutes(10),
        ]);

        $token = app(AccessTokenService::class)->createForUser($admin, 'phpunit-operations');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/operations/overview')
            ->assertOk()
            ->assertJsonPath('datos.summary.jobs.failed_last_24h', 1)
            ->assertJsonPath('datos.summary.transfers.failed_last_24h', 1)
            ->assertJsonPath('datos.summary.notifications.unread', 1)
            ->assertJsonPath('datos.summary.security.warnings_last_24h', 1)
            ->assertJsonPath('meta.window_hours', 24);
    }
}
