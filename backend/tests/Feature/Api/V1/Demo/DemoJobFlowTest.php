<?php

namespace Tests\Feature\Api\V1\Demo;

use App\Jobs\Demo\ProcessDemoJobRun;
use App\Core\Jobs\Models\CoreJobRun;
use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemoJobFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_dispatch_demo_job_to_queue(): void
    {
        Queue::fake();
        [$user, $token] = $this->authenticateUser();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/jobs', [
                'message' => 'Procesar resumen comercial',
                'mode' => 'queued',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('datos.status', 'pending')
            ->assertJsonPath('datos.requested_payload.mode', 'queued')
            ->assertJsonPath('meta.worker_hint', 'Ejecuta php artisan queue:work --queue=demo para procesar jobs pendientes.');

        Queue::assertPushed(ProcessDemoJobRun::class, 1);

        $this->assertDatabaseHas('core_job_runs', [
            'organizacion_id' => $user->organizacion_activa_id,
            'status' => 'pending',
            'job_key' => 'demo.text-transform',
        ]);
    }

    public function test_user_can_run_demo_job_immediately(): void
    {
        [$user, $token] = $this->authenticateUser();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/jobs', [
                'message' => 'stackbase jobs listos',
                'mode' => 'immediate',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('datos.status', 'completed')
            ->assertJsonPath('datos.result_payload.uppercase_message', 'STACKBASE JOBS LISTOS')
            ->assertJsonPath('datos.requested_by', $user->name);
    }

    public function test_immediate_demo_job_can_fail_and_log_error(): void
    {
        [$user, $token] = $this->authenticateUser();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/jobs', [
                'message' => 'falla controlada',
                'mode' => 'immediate',
                'should_fail' => true,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('datos.status', 'failed')
            ->assertJsonPath('mensaje', 'Job demo ejecutado con fallo controlado');

        $this->assertDatabaseHas('core_job_runs', [
            'organizacion_id' => $user->organizacion_activa_id,
            'status' => 'failed',
        ]);
    }

    public function test_queued_demo_job_keeps_runtime_tenant_and_actor_context(): void
    {
        [$user, $token] = $this->authenticateUser();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/jobs', [
                'message' => 'contexto de cola',
                'mode' => 'queued',
            ]);

        $response->assertOk();

        $jobRun = CoreJobRun::query()->latest('id')->firstOrFail();

        (new ProcessDemoJobRun($jobRun->id))->handle(
            app(\App\Core\Jobs\Services\CoreJobRunner::class),
            app(\App\Core\Tenancy\TenantContext::class),
        );

        $jobRun->refresh();

        $this->assertSame('completed', $jobRun->status);
        $this->assertSame($user->organizacion_activa_id, data_get($jobRun->result_payload, 'runtime_context.organizacion_id'));
        $this->assertSame($user->id, data_get($jobRun->result_payload, 'runtime_context.actor_id'));
    }

    public function test_failed_demo_job_can_be_retried_from_api(): void
    {
        Queue::fake();
        [$user, $token] = $this->authenticateUser();

        $failedResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/jobs', [
                'message' => 'falla reintentable',
                'mode' => 'immediate',
                'should_fail' => true,
            ]);

        $failedResponse->assertOk();

        $jobRun = CoreJobRun::query()->latest('id')->firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/demo/jobs/{$jobRun->uuid}/retry")
            ->assertOk()
            ->assertJsonPath('datos.status', 'pending')
            ->assertJsonPath('datos.can_retry', true)
            ->assertJsonPath('meta.worker_hint', 'Ejecuta php artisan queue:work --queue=demo para procesar jobs pendientes.');

        Queue::assertPushed(ProcessDemoJobRun::class, 1);

        $this->assertDatabaseHas('core_job_runs', [
            'id' => $jobRun->id,
            'status' => 'pending',
            'organizacion_id' => $user->organizacion_activa_id,
        ]);
    }

    protected function authenticateUser(): array
    {
        $organizacion = Organizacion::query()->create([
            'nombre' => 'Acme Jobs',
            'slug' => 'acme-jobs',
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
}
