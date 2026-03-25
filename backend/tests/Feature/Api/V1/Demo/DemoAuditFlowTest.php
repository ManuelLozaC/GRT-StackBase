<?php

namespace Tests\Feature\Api\V1\Demo;

use App\Models\Organizacion;
use App\Models\User;
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

    protected function authenticateUser(): array
    {
        $organizacion = Organizacion::query()->create([
            'nombre' => 'Acme Audit',
            'slug' => 'acme-audit',
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
