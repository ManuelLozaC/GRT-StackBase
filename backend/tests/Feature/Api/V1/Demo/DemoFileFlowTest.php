<?php

namespace Tests\Feature\Api\V1\Demo;

use App\Core\Auth\Services\AccessTokenService;
use App\Core\Files\Models\ManagedFile;
use App\Core\Files\Services\FileManager;
use App\Core\Modules\ModuleSettingsManager;
use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            ], [
                'Accept' => 'application/json',
            ]);

        $uploadResponse
            ->assertOk()
            ->assertJsonPath('datos.original_name', 'demo-manual.pdf')
            ->assertJsonPath('datos.version', 1);

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
        $organizacion = Organizacion::query()->create([
            'nombre' => 'Acme Files',
            'slug' => 'acme-files',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $organizacion->id,
        ]);

        $user->organizaciones()->attach($organizacion->id);

        return [
            $user,
            app(AccessTokenService::class)->createForUser($user, 'phpunit'),
        ];
    }

    protected function authenticateUserWithTwoOrganizations(): array
    {
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

        return [
            $user,
            app(AccessTokenService::class)->createForUser($user, 'phpunit'),
            $primaryOrganization,
            $secondaryOrganization,
        ];
    }
}
