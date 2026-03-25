<?php

namespace Tests\Feature\Api\V1\Demo;

use App\Jobs\Demo\ProcessDemoJobRun;
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
