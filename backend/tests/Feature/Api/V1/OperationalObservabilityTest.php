<?php

namespace Tests\Feature\Api\V1;

use App\Core\Auth\Services\AccessTokenService;
use App\Core\Errors\ErrorLogger;
use App\Core\Errors\Models\CoreErrorLog;
use App\Core\Metrics\Models\CoreMetricEvent;
use App\Core\Metrics\MetricsRecorder;
use App\Core\Tenancy\TenantContext;
use App\Models\Organizacion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OperationalObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unhandled_api_exception_is_logged_and_returns_standard_payload(): void
    {
        Route::middleware('api')->get('/api/v1/test-runtime-error', function (): void {
            throw new \RuntimeException('Boom');
        });

        $response = $this->getJson('/api/v1/test-runtime-error');

        $response->assertStatus(500)
            ->assertJsonPath('estado', 'error')
            ->assertJsonPath('meta.error_code', 'internal_error')
            ->assertJsonStructure([
                'estado',
                'datos',
                'mensaje',
                'meta' => ['request_id', 'error_code', 'error_log_id'],
                'errores',
            ]);

        $this->assertDatabaseHas('core_error_logs', [
            'error_code' => 'internal_error',
            'error_class' => \RuntimeException::class,
        ]);
    }

    public function test_admin_can_list_error_logs_and_metrics_overview(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $organization = Organizacion::query()->create([
            'nombre' => 'Observability Tenant',
            'slug' => 'observability-tenant',
        ]);

        $admin = User::factory()->create([
            'email' => 'observability-admin@stackbase.local',
            'organizacion_activa_id' => $organization->id,
        ]);
        $admin->organizaciones()->attach($organization->id);
        $admin->assignRole('admin');

        CoreErrorLog::query()->create([
            'organizacion_id' => $organization->id,
            'actor_id' => $admin->id,
            'request_id' => 'req-observability-1',
            'error_class' => \RuntimeException::class,
            'error_code' => 'internal_error',
            'message' => 'Synthetic error',
            'occurred_at' => now()->subMinutes(5),
        ]);

        CoreMetricEvent::query()->create([
            'organizacion_id' => $organization->id,
            'actor_id' => $admin->id,
            'module_key' => 'demo-platform',
            'event_key' => 'demo.file.uploaded',
            'event_category' => 'files',
            'request_id' => 'req-metric-1',
            'context' => ['source' => 'test'],
            'occurred_at' => now()->subMinutes(4),
        ]);

        CoreMetricEvent::query()->create([
            'organizacion_id' => $organization->id,
            'actor_id' => $admin->id,
            'module_key' => 'core-platform',
            'event_key' => 'auth.login_succeeded',
            'event_category' => 'auth',
            'request_id' => 'req-metric-2',
            'context' => ['source' => 'test'],
            'occurred_at' => now()->subMinutes(3),
        ]);

        $token = app(AccessTokenService::class)->createForUser($admin, 'phpunit-observability');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/error-logs')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.error_code', 'internal_error');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/metrics/overview')
            ->assertOk()
            ->assertJsonStructure([
                'datos' => [
                    'summary' => [
                        'events_last_24h',
                        'active_modules_last_24h',
                        'active_categories_last_24h',
                        'average_response_time_ms',
                        'slow_requests_last_24h',
                    ],
                    'recent_slow_requests',
                ],
            ]);

        $metricsPayload = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/metrics/overview')
            ->json('datos.summary');

        $this->assertGreaterThanOrEqual(2, $metricsPayload['events_last_24h']);
        $this->assertGreaterThanOrEqual(1, $metricsPayload['active_modules_last_24h']);
        $this->assertGreaterThanOrEqual(1, $metricsPayload['active_categories_last_24h']);
    }

    public function test_error_logs_support_filters(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $organization = Organizacion::query()->create([
            'nombre' => 'Error Filter Tenant',
            'slug' => 'error-filter-tenant',
        ]);

        $admin = User::factory()->create([
            'email' => 'error-filter-admin@stackbase.local',
            'organizacion_activa_id' => $organization->id,
        ]);
        $admin->organizaciones()->attach($organization->id);
        $admin->assignRole('admin');

        CoreErrorLog::query()->create([
            'organizacion_id' => $organization->id,
            'actor_id' => $admin->id,
            'request_id' => 'req-error-1',
            'error_class' => \RuntimeException::class,
            'error_code' => 'validation_error',
            'message' => 'Error de validacion',
            'occurred_at' => now()->subMinutes(4),
        ]);

        CoreErrorLog::query()->create([
            'organizacion_id' => $organization->id,
            'actor_id' => $admin->id,
            'request_id' => 'req-error-2',
            'error_class' => \InvalidArgumentException::class,
            'error_code' => 'internal_error',
            'message' => 'Error interno',
            'occurred_at' => now()->subMinutes(2),
        ]);

        $token = app(AccessTokenService::class)->createForUser($admin, 'phpunit-error-filter');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/error-logs?error_code=validation_error&request_id=req-error-1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.error_code', 'validation_error');
    }

    public function test_metrics_and_error_logs_can_inherit_actor_from_tenant_context(): void
    {
        config()->set('app.env', 'production');
        putenv('CORE_METRICS_ENABLED=true');
        $_ENV['CORE_METRICS_ENABLED'] = 'true';
        $_SERVER['CORE_METRICS_ENABLED'] = 'true';

        $organization = Organizacion::query()->create([
            'nombre' => 'Context Observability',
            'slug' => 'context-observability',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $organization->id,
        ]);
        $user->organizaciones()->attach($organization->id);

        $tenantContext = app(TenantContext::class);
        $tenantContext->setFromUser($user);
        request()->attributes->set('request_id', 'req-context-observability');

        try {
            app(MetricsRecorder::class)->record(
                moduleKey: 'demo-platform',
                eventKey: 'demo.context.metric',
                eventCategory: 'demo',
            );

            app(ErrorLogger::class)->log(
                new \RuntimeException('Context observability error'),
                'context_error',
                ['source' => 'tenant-context'],
            );
        } finally {
            $tenantContext->clear();
        }

        $this->assertDatabaseHas('core_metric_events', [
            'event_key' => 'demo.context.metric',
            'organizacion_id' => $organization->id,
            'actor_id' => $user->id,
        ]);

        $this->assertDatabaseHas('core_error_logs', [
            'error_code' => 'context_error',
            'organizacion_id' => $organization->id,
            'actor_id' => $user->id,
        ]);

        putenv('CORE_METRICS_ENABLED');
        unset($_ENV['CORE_METRICS_ENABLED'], $_SERVER['CORE_METRICS_ENABLED']);
    }
}
