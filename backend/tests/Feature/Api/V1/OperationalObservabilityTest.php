<?php

namespace Tests\Feature\Api\V1;

use App\Core\Auth\Services\AccessTokenService;
use App\Core\Errors\Models\CoreErrorLog;
use App\Core\Metrics\Models\CoreMetricEvent;
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
            ->assertJsonPath('datos.summary.events_last_24h', 2)
            ->assertJsonPath('datos.summary.active_modules_last_24h', 2)
            ->assertJsonPath('datos.summary.active_categories_last_24h', 2);
    }
}
