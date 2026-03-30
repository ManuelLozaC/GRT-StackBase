<?php

namespace Tests\Feature\Api\V1\Demo;

use App\Core\Auth\Services\AccessTokenService;
use App\Core\Files\Models\ManagedFile;
use App\Core\Files\Services\FileManager;
use App\Core\Jobs\Models\CoreJobRun;
use App\Core\Modules\ModuleSettingsManager;
use App\Jobs\Demo\ProcessDemoFilePackageRun;
use App\Models\Organizacion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoFileFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_and_list_demo_files(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        [$user, $token] = $this->authenticateUser();

        $uploadResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/demo/files', [
                'file' => UploadedFile::fake()->create('demo-manual.pdf', 120, 'application/pdf'),
                'notes' => 'Archivo demo de prueba',
                'attached_resource_key' => 'people',
                'attached_record_id' => 15,
                'attached_record_label' => 'Maria Suarez',
            ], [
                'Accept' => 'application/json',
            ]);

        $uploadResponse
            ->assertOk()
            ->assertJsonPath('datos.original_name', 'demo-manual.pdf')
            ->assertJsonPath('datos.version', 1)
            ->assertJsonPath('datos.attachment.resource_key', 'people')
            ->assertJsonPath('datos.attachment.record_id', 15)
            ->assertJsonPath('datos.attachment.record_label', 'Maria Suarez');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/files')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'original_name' => 'demo-manual.pdf',
            ]);

        $file = ManagedFile::query()->firstOrFail();

        Storage::disk('local')->assertExists($file->path);
        $this->assertSame($user->organizacion_activa_id, $file->organizacion_id);
        $this->assertSame('people', $file->attached_resource_key);
        $this->assertSame(15, $file->attached_record_id);
        $this->assertSame('Maria Suarez', $file->attached_record_label);
    }

    public function test_upload_falls_back_to_local_disk_when_spaces_is_not_configured(): void
    {
        Storage::fake('local');
        config([
            'filesystems.default' => 'spaces',
            'filesystems.fallback_disk' => 'local',
            'filesystems.disks.spaces.key' => null,
            'filesystems.disks.spaces.secret' => null,
            'filesystems.disks.spaces.bucket' => null,
            'filesystems.disks.spaces.endpoint' => null,
        ]);

        [$user] = $this->authenticateUser();

        $file = app(FileManager::class)->storeUploadedFile(
            UploadedFile::fake()->createWithContent('fallback.txt', 'fallback content'),
            $user,
            ['source' => 'phpunit'],
        );

        $this->assertSame('local', $file->disk);
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_user_can_upload_new_file_version_and_list_history(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        [$user, $token] = $this->authenticateUser();

        $originalFile = app(FileManager::class)->storeUploadedFile(
            UploadedFile::fake()->createWithContent('contrato-v1.pdf', 'version 1'),
            $user,
            ['source' => 'phpunit'],
            [
                'resource_key' => 'people',
                'record_id' => 15,
                'record_label' => 'Maria Suarez',
            ],
        );

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post("/api/v1/demo/files/{$originalFile->uuid}/versions", [
                'file' => UploadedFile::fake()->createWithContent('contrato-v2.pdf', 'version 2'),
                'notes' => 'Segunda version',
            ], [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('datos.version', 2)
            ->assertJsonPath('datos.previous_version_uuid', $originalFile->uuid);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/files')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'original_name' => 'contrato-v2.pdf',
            ])
            ->assertJsonMissing([
                'original_name' => 'contrato-v1.pdf',
            ]);

        $historyResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/files/'.$response->json('datos.uuid').'/versions');

        $historyResponse
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('datos.0.version', 2)
            ->assertJsonPath('datos.1.version', 1);

        $originalFile->refresh();
        $latestFile = ManagedFile::query()->latest('id')->firstOrFail();

        $this->assertNotNull($originalFile->superseded_at);
        $this->assertSame($originalFile->version_group_uuid, $latestFile->version_group_uuid);
        $this->assertSame($originalFile->id, $latestFile->previous_version_id);
        $this->assertSame('people', $latestFile->attached_resource_key);
        $this->assertSame(15, $latestFile->attached_record_id);
    }

    public function test_user_can_queue_async_file_package_and_download_when_processed(): void
    {
        Storage::fake('local');
        config([
            'filesystems.default' => 'local',
            'queue.default' => 'sync',
        ]);

        [$user, $token] = $this->authenticateUser();

        $first = app(FileManager::class)->storeUploadedFile(
            UploadedFile::fake()->createWithContent('propuesta.pdf', 'contenido uno'),
            $user,
            ['source' => 'phpunit'],
        );
        $second = app(FileManager::class)->storeUploadedFile(
            UploadedFile::fake()->createWithContent('resumen.txt', 'contenido dos'),
            $user,
            ['source' => 'phpunit'],
        );

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/files/packages', [
                'file_uuids' => [$first->uuid, $second->uuid],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('datos.file_count', 2);

        $run = CoreJobRun::query()->where('job_key', 'demo.files.package')->firstOrFail();
        $this->assertSame('completed', $run->fresh()->status);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/files/packages')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.download_url', '/api/v1/demo/files/packages/'.$run->uuid.'/download');

        $downloadResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/demo/files/packages/'.$run->uuid.'/download');

        $downloadResponse->assertOk();
        $this->assertStringContainsString('.zip', (string) $downloadResponse->headers->get('content-disposition'));
    }

    public function test_user_can_retry_failed_async_file_package(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        Queue::fake();

        [$user, $token] = $this->authenticateUser();

        $run = CoreJobRun::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'organizacion_id' => $user->organizacion_activa_id,
            'requested_by' => $user->id,
            'job_key' => 'demo.files.package',
            'queue' => 'files',
            'status' => 'failed',
            'requested_payload' => [
                'file_uuids' => ['missing-uuid'],
                'file_count' => 1,
                'original_names' => ['faltante.txt'],
            ],
            'attempts' => 3,
            'error_message' => 'fallo previo',
            'failed_at' => now(),
            'finished_at' => now(),
            'dispatched_at' => now()->subMinute(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/files/packages/'.$run->uuid.'/retry')
            ->assertOk()
            ->assertJsonPath('datos.status', 'pending');

        Queue::assertPushed(ProcessDemoFilePackageRun::class);
        $this->assertSame('pending', $run->fresh()->status);
        $this->assertNull($run->fresh()->error_message);
    }

    public function test_direct_download_is_recorded_in_history(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        [$user, $token] = $this->authenticateUser();
        $file = app(FileManager::class)->storeUploadedFile(
            UploadedFile::fake()->createWithContent('stackbase.txt', 'demo content'),
            $user,
            ['source' => 'phpunit'],
        );

        $downloadResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/demo/files/'.$file->uuid.'/download');

        $downloadResponse->assertOk();
        $this->assertStringContainsString(
            'stackbase.txt',
            (string) $downloadResponse->headers->get('content-disposition'),
        );

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/files/downloads')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'channel' => 'direct',
                'original_name' => 'stackbase.txt',
            ]);
    }

    public function test_temporary_link_can_be_generated_and_used(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        [$user, $token] = $this->authenticateUser();
        $file = app(FileManager::class)->storeUploadedFile(
            UploadedFile::fake()->createWithContent('signed-demo.txt', 'signed content'),
            $user,
            ['source' => 'phpunit'],
        );

        $linkResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/files/'.$file->uuid.'/temporary-link', [
                'ttl_minutes' => 15,
            ]);

        $linkResponse
            ->assertOk()
            ->assertJsonPath('mensaje', 'Link temporal generado');

        $url = $linkResponse->json('datos.url');
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $temporaryDownloadResponse = $this->get($path.'?'.$query);

        $temporaryDownloadResponse->assertOk();
        $this->assertStringContainsString(
            'signed-demo.txt',
            (string) $temporaryDownloadResponse->headers->get('content-disposition'),
        );

        $this->assertDatabaseHas('core_file_downloads', [
            'managed_file_id' => $file->id,
            'channel' => 'signed-url',
        ]);
    }

    public function test_files_and_downloads_are_scoped_by_active_organization(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        [$user, $token, $primaryOrganization, $secondaryOrganization] = $this->authenticateUserWithTwoOrganizations();

        $user->forceFill([
            'organizacion_activa_id' => $primaryOrganization->id,
        ])->save();

        $primaryFile = app(FileManager::class)->storeUploadedFile(
            UploadedFile::fake()->createWithContent('primary-tenant.txt', 'primary'),
            $user->fresh(),
            ['source' => 'phpunit'],
        );

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/demo/files/'.$primaryFile->uuid.'/download')
            ->assertOk();

        $user->forceFill([
            'organizacion_activa_id' => $secondaryOrganization->id,
        ])->save();

        $secondaryFile = app(FileManager::class)->storeUploadedFile(
            UploadedFile::fake()->createWithContent('secondary-tenant.txt', 'secondary'),
            $user->fresh(),
            ['source' => 'phpunit'],
        );

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $secondaryOrganization->id,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/demo/files/'.$secondaryFile->uuid.'/download')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $primaryOrganization->id,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/files')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'original_name' => 'primary-tenant.txt',
            ])
            ->assertJsonMissing([
                'original_name' => 'secondary-tenant.txt',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/files/downloads')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'original_name' => 'primary-tenant.txt',
            ])
            ->assertJsonMissing([
                'original_name' => 'secondary-tenant.txt',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $secondaryOrganization->id,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/files')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'original_name' => 'secondary-tenant.txt',
            ])
            ->assertJsonMissing([
                'original_name' => 'primary-tenant.txt',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/demo/files/downloads')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'original_name' => 'secondary-tenant.txt',
            ])
            ->assertJsonMissing([
                'original_name' => 'primary-tenant.txt',
            ]);
    }

    public function test_temporary_link_uses_module_default_ttl_setting_when_not_provided(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        [$user, $token] = $this->authenticateUser();
        app(ModuleSettingsManager::class)->update('demo-platform', [
            'default_file_ttl_minutes' => 45,
        ]);

        $file = app(FileManager::class)->storeUploadedFile(
            UploadedFile::fake()->createWithContent('ttl-demo.txt', 'ttl content'),
            $user,
            ['source' => 'phpunit'],
        );

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/demo/files/'.$file->uuid.'/temporary-link', []);

        $response
            ->assertOk()
            ->assertJsonPath('mensaje', 'Link temporal generado');

        $expires = (int) parse_url($response->json('datos.url'), PHP_URL_QUERY);
        $this->assertNotEmpty($response->json('datos.url'));
    }

    protected function authenticateUser(): array
    {
        $this->seed(RolePermissionSeeder::class);

        $organizacion = Organizacion::query()->create([
            'nombre' => 'Acme Files',
            'slug' => 'acme-files',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $organizacion->id,
        ]);

        $user->organizaciones()->attach($organizacion->id);
        $user->givePermissionTo('demo.access');

        return [
            $user,
            app(AccessTokenService::class)->createForUser($user, 'phpunit'),
        ];
    }

    protected function authenticateUserWithTwoOrganizations(): array
    {
        $this->seed(RolePermissionSeeder::class);

        $primaryOrganization = Organizacion::query()->create([
            'nombre' => 'Acme Files Primary',
            'slug' => 'acme-files-primary',
        ]);

        $secondaryOrganization = Organizacion::query()->create([
            'nombre' => 'Acme Files Secondary',
            'slug' => 'acme-files-secondary',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $primaryOrganization->id,
        ]);

        $user->organizaciones()->attach([
            $primaryOrganization->id,
            $secondaryOrganization->id,
        ]);
        $user->givePermissionTo('demo.access');

        return [
            $user,
            app(AccessTokenService::class)->createForUser($user, 'phpunit'),
            $primaryOrganization,
            $secondaryOrganization,
        ];
    }
}
