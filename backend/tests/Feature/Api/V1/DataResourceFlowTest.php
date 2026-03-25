<?php

namespace Tests\Feature\Api\V1;

use App\Core\Auth\Services\AccessTokenService;
use App\Core\DataEngine\Models\CoreDataTransferRun;
use App\Core\DataEngine\Services\DataTransferManager;
use App\Core\Modules\ModuleRegistry;
use App\Core\Tenancy\TenantContext;
use App\Jobs\DataEngine\ProcessDataExportRun;
use App\Models\Organizacion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataResourceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_available_resources_only_when_source_module_is_enabled(): void
    {
        [$user, $token] = $this->authenticateUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/resources')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        app(ModuleRegistry::class)->setEnabled('demo-platform', true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/resources')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'key' => 'demo-contacts',
                'name' => 'Demo Contacts',
            ]);
    }

    public function test_user_can_crud_demo_contacts_with_search_filters_pagination_and_soft_delete(): void
    {
        [$user, $token] = $this->authenticateUser();
        app(ModuleRegistry::class)->setEnabled('demo-platform', true);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/demo-contacts', [
                'nombre' => 'Acme Lead',
                'email' => 'lead@acme.test',
                'telefono' => '70000001',
                'empresa' => 'Acme',
                'estado' => 'lead',
                'prioridad' => 'high',
                'notas' => 'Contacto inicial',
            ]);

        $createResponse
            ->assertOk()
            ->assertJsonPath('datos.nombre', 'Acme Lead')
            ->assertJsonPath('datos.estado', 'lead');

        $recordId = $createResponse->json('datos.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/demo-contacts', [
            'nombre' => 'Beta Contact',
            'email' => 'beta@test.dev',
            'telefono' => '70000002',
            'empresa' => 'Beta',
            'estado' => 'active',
            'prioridad' => 'medium',
            'notas' => 'Otro contacto',
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/demo-contacts?per_page=1&page=1&sort_by=nombre&sort_direction=asc&q=Acme&filters[estado]=lead')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('meta.pagination.per_page', 1)
            ->assertJsonPath('datos.0.nombre', 'Acme Lead');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/data/demo-contacts/'.$recordId, [
                'estado' => 'active',
                'prioridad' => 'low',
            ])
            ->assertOk()
            ->assertJsonPath('datos.estado', 'active')
            ->assertJsonPath('datos.prioridad', 'low');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/demo-contacts/'.$recordId)
            ->assertOk()
            ->assertJsonPath('datos.id', $recordId)
            ->assertJsonPath('datos.estado', 'active');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/data/demo-contacts/'.$recordId)
            ->assertOk()
            ->assertJsonPath('mensaje', 'Registro eliminado correctamente');

        $this->assertSoftDeleted('demo_contacts', [
            'id' => $recordId,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/demo-contacts/'.$recordId)
            ->assertStatus(404);
    }

    public function test_demo_contacts_are_scoped_by_active_organization(): void
    {
        [$user, $token, $primaryOrganization, $secondaryOrganization] = $this->authenticateUserWithTwoOrganizations();
        app(ModuleRegistry::class)->setEnabled('demo-platform', true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/demo-contacts', [
                'nombre' => 'Primary Contact',
                'estado' => 'active',
                'prioridad' => 'medium',
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $secondaryOrganization->id,
            ])
            ->assertOk();

        $secondaryContactResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/demo-contacts', [
                'nombre' => 'Secondary Contact',
                'estado' => 'lead',
                'prioridad' => 'high',
            ])
            ->assertOk();

        $secondaryContactId = $secondaryContactResponse->json('datos.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $primaryOrganization->id,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/demo-contacts')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonFragment([
                'nombre' => 'Primary Contact',
            ])
            ->assertJsonMissing([
                'nombre' => 'Secondary Contact',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/demo-contacts/'.$secondaryContactId)
            ->assertStatus(404);
    }

    public function test_user_can_export_demo_contacts_and_the_transfer_is_logged(): void
    {
        [$user, $token] = $this->authenticateUser();
        app(ModuleRegistry::class)->setEnabled('demo-platform', true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/demo-contacts', [
                'nombre' => 'Acme Lead',
                'email' => 'lead@acme.test',
                'estado' => 'active',
                'prioridad' => 'medium',
            ])
            ->assertOk();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/data/demo-contacts/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = (string) $response->getContent();
        $this->assertStringContainsString('nombre,email,telefono,empresa,estado,prioridad,notas', $content);
        $this->assertStringContainsString('"Acme Lead",lead@acme.test,,,active,medium,', $content);

        $this->assertDatabaseHas('core_data_transfer_runs', [
            'resource_key' => 'demo-contacts',
            'type' => 'export',
            'status' => 'completed',
            'records_processed' => 1,
        ]);
    }

    public function test_user_can_export_demo_contacts_in_excel_and_pdf_formats(): void
    {
        [$user, $token] = $this->authenticateUser();
        app(ModuleRegistry::class)->setEnabled('demo-platform', true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/demo-contacts', [
                'nombre' => 'Acme Lead',
                'email' => 'lead@acme.test',
                'estado' => 'active',
                'prioridad' => 'medium',
            ])
            ->assertOk();

        $excelResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/data/demo-contacts/export?format=excel');

        $excelResponse->assertOk();
        $this->assertStringContainsString('application/vnd.ms-excel', (string) $excelResponse->headers->get('content-type'));
        $this->assertStringContainsString('<table', (string) $excelResponse->getContent());

        $pdfResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/data/demo-contacts/export?format=pdf');

        $pdfResponse->assertOk();
        $this->assertSame('application/pdf', $pdfResponse->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', (string) $pdfResponse->getContent());
    }

    public function test_user_can_import_demo_contacts_from_csv_and_list_transfer_history(): void
    {
        [$user, $token] = $this->authenticateUser();
        app(ModuleRegistry::class)->setEnabled('demo-platform', true);

        $file = UploadedFile::fake()->createWithContent('demo-contacts.csv', <<<CSV
nombre,email,telefono,empresa,estado,prioridad,notas
Import One,import1@test.dev,70000010,Acme,active,high,Primera fila
Import Two,import2@test.dev,70000011,Beta,lead,medium,Segunda fila
CSV);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/data/demo-contacts/import', [
                'file' => $file,
            ])
            ->assertOk()
            ->assertJsonPath('datos.status', 'completed')
            ->assertJsonPath('datos.records_processed', 2);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/demo-contacts')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 2)
            ->assertJsonFragment([
                'nombre' => 'Import One',
            ])
            ->assertJsonFragment([
                'nombre' => 'Import Two',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/demo-contacts/transfers')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.type', 'import')
            ->assertJsonPath('datos.0.records_processed', 2);
    }

    public function test_transfer_history_is_scoped_by_active_organization(): void
    {
        [$user, $token, $primaryOrganization, $secondaryOrganization] = $this->authenticateUserWithTwoOrganizations();
        app(ModuleRegistry::class)->setEnabled('demo-platform', true);

        CoreDataTransferRun::query()->create([
            'organizacion_id' => $primaryOrganization->id,
            'requested_by' => $user->id,
            'resource_key' => 'demo-contacts',
            'source_module' => 'demo-platform',
            'type' => 'export',
            'status' => 'completed',
            'records_total' => 1,
            'records_processed' => 1,
            'records_failed' => 0,
            'file_name' => 'primary.csv',
            'mime_type' => 'text/csv',
            'finished_at' => now(),
        ]);

        CoreDataTransferRun::query()->withoutGlobalScopes()->create([
            'organizacion_id' => $secondaryOrganization->id,
            'requested_by' => $user->id,
            'resource_key' => 'demo-contacts',
            'source_module' => 'demo-platform',
            'type' => 'import',
            'status' => 'completed',
            'records_total' => 1,
            'records_processed' => 1,
            'records_failed' => 0,
            'file_name' => 'secondary.csv',
            'mime_type' => 'text/csv',
            'finished_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/demo-contacts/transfers')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.file_name', 'primary.csv');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $secondaryOrganization->id,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/demo-contacts/transfers')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('datos.0.file_name', 'secondary.csv');
    }

    public function test_user_can_queue_async_export_and_download_result(): void
    {
        Storage::fake('local');
        config([
            'filesystems.data_exports_disk' => 'spaces',
            'filesystems.fallback_disk' => 'local',
            'filesystems.disks.spaces.key' => null,
            'filesystems.disks.spaces.secret' => null,
            'filesystems.disks.spaces.bucket' => null,
            'filesystems.disks.spaces.endpoint' => null,
        ]);

        [$user, $token] = $this->authenticateUser();
        app(ModuleRegistry::class)->setEnabled('demo-platform', true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/demo-contacts', [
                'nombre' => 'Async Contact',
                'email' => 'async@test.dev',
                'estado' => 'lead',
                'prioridad' => 'high',
            ])
            ->assertOk();

        $queueResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/demo-contacts/export?format=pdf&mode=async');

        $queueResponse
            ->assertOk()
            ->assertJsonPath('datos.status', 'queued')
            ->assertJsonPath('datos.mode', 'async')
            ->assertJsonPath('datos.format', 'pdf');

        $run = CoreDataTransferRun::query()->latest('id')->firstOrFail();

        $job = new ProcessDataExportRun($run->id);
        $job->handle(
            app(DataTransferManager::class),
            app(\App\Core\DataEngine\DataResourceRegistry::class),
            app(TenantContext::class),
        );

        $run = $run->fresh();

        $this->assertSame('completed', $run->status);
        $this->assertSame('local', $run->metadata['storage_disk'] ?? null);
        $this->assertNotNull($run->metadata['storage_path'] ?? null);
        Storage::disk('local')->assertExists($run->metadata['storage_path']);

        $downloadResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/v1/data/transfers/'.$run->uuid.'/download');

        $downloadResponse->assertOk();
        $this->assertSame('application/pdf', $downloadResponse->headers->get('content-type'));
        $content = method_exists($downloadResponse, 'streamedContent')
            ? (string) $downloadResponse->streamedContent()
            : (string) $downloadResponse->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
    }

    public function test_admin_can_manage_tenant_structures_and_use_relations_and_custom_fields(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$user, $token] = $this->authenticateUser();
        $user->assignRole('admin');
        app(ModuleRegistry::class)->setEnabled('demo-platform', true);

        $companyResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/tenant-companies', [
                'nombre' => 'Acme Corp',
                'slug' => 'acme-corp',
            ])
            ->assertOk();

        $companyId = $companyResponse->json('datos.id');

        $branchResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/tenant-branches', [
                'nombre' => 'Casa Matriz',
                'slug' => 'casa-matriz',
                'empresa_id' => $companyId,
            ])
            ->assertOk();

        $branchId = $branchResponse->json('datos.id');

        $teamResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/tenant-teams', [
                'nombre' => 'Ventas B2B',
                'slug' => 'ventas-b2b',
                'sucursal_id' => $branchId,
            ])
            ->assertOk();

        $contactResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/demo-contacts', [
                'nombre' => 'Relacion Demo',
                'estado' => 'active',
                'prioridad' => 'high',
                'empresa_id' => $companyId,
                'sucursal_id' => $branchId,
                'equipo_id' => $teamResponse->json('datos.id'),
                'custom_fields' => [
                    'segmento' => 'Enterprise',
                    'canal_origen' => 'web',
                    'presupuesto_estimado' => '25000',
                ],
            ])
            ->assertOk();

        $recordId = $contactResponse->json('datos.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/demo-contacts/'.$recordId)
            ->assertOk()
            ->assertJsonPath('datos.empresa_id', $companyId)
            ->assertJsonPath('datos.empresa_id_label', 'Acme Corp')
            ->assertJsonPath('datos.sucursal_id_label', 'Casa Matriz')
            ->assertJsonPath('datos.equipo_id_label', 'Ventas B2B')
            ->assertJsonPath('datos.custom_fields.segmento', 'Enterprise')
            ->assertJsonPath('datos.custom_fields.canal_origen', 'web');
    }

    public function test_admin_can_manage_base_domain_resources_with_data_engine(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$user, $token] = $this->authenticateUser();
        $user->assignRole('admin');

        $officeResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/offices', [
                'nombre' => 'Oficina Norte',
                'slug' => 'oficina-norte',
                'codigo' => 'NORTE',
                'ciudad' => 'Santa Cruz',
                'pais' => 'Bolivia',
                'activa' => true,
            ])
            ->assertOk();

        $officeId = $officeResponse->json('datos.id');

        $personResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/people', [
                'nombres' => 'Maria',
                'apellido_paterno' => 'Suarez',
                'correo' => 'maria@test.dev',
                'telefono' => '70000003',
                'sexo' => 'femenino',
                'ciudad' => 'Santa Cruz',
                'pais' => 'Bolivia',
                'activa' => true,
            ])
            ->assertOk();

        $personId = $personResponse->json('datos.id');

        $managerPersonId = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/people', [
                'nombres' => 'Laura',
                'apellido_paterno' => 'Mendez',
                'correo' => 'laura@test.dev',
                'telefono' => '70000004',
                'sexo' => 'femenino',
                'ciudad' => 'Santa Cruz',
                'pais' => 'Bolivia',
                'activa' => true,
            ])
            ->assertOk()
            ->json('datos.id');

        $divisionId = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/divisions', [
                'nombre' => 'Comercial',
                'slug' => 'comercial',
                'activa' => true,
            ])
            ->assertOk()
            ->json('datos.id');

        $areaId = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/areas', [
                'nombre' => 'Ventas',
                'slug' => 'ventas',
                'division_id' => $divisionId,
                'activa' => true,
            ])
            ->assertOk()
            ->json('datos.id');

        $cargoId = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/positions', [
                'nombre' => 'Ejecutiva de Ventas',
                'slug' => 'ejecutiva-de-ventas',
                'activa' => true,
            ])
            ->assertOk()
            ->json('datos.id');

        $managerCargoId = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/positions', [
                'nombre' => 'Gerente Comercial',
                'slug' => 'gerente-comercial',
                'activa' => true,
            ])
            ->assertOk()
            ->json('datos.id');

        $managerAssignmentId = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/work-assignments', [
                'persona_id' => $managerPersonId,
                'oficina_id' => $officeId,
                'division_id' => $divisionId,
                'area_id' => $areaId,
                'cargo_id' => $managerCargoId,
                'es_principal' => true,
                'estado' => 'active',
                'fecha_inicio' => '2026-03-20',
            ])
            ->assertOk()
            ->json('datos.id');

        $assignmentResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/work-assignments', [
                'persona_id' => $personId,
                'oficina_id' => $officeId,
                'division_id' => $divisionId,
                'area_id' => $areaId,
                'cargo_id' => $cargoId,
                'jefe_asignacion_id' => $managerAssignmentId,
                'aprobador_asignacion_id' => $managerAssignmentId,
                'es_principal' => true,
                'estado' => 'active',
                'fecha_inicio' => '2026-03-25',
            ])
            ->assertOk();

        $assignmentId = $assignmentResponse->json('datos.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/work-assignments/'.$assignmentId)
            ->assertOk()
            ->assertJsonPath('datos.persona_id', $personId)
            ->assertJsonPath('datos.persona_id_label', 'Maria Suarez')
            ->assertJsonPath('datos.oficina_id', $officeId)
            ->assertJsonPath('datos.oficina_id_label', 'Oficina Norte')
            ->assertJsonPath('datos.division_id_label', 'Comercial')
            ->assertJsonPath('datos.area_id_label', 'Ventas')
            ->assertJsonPath('datos.cargo_id_label', 'Ejecutiva de Ventas')
            ->assertJsonPath('datos.jefe_asignacion_id_label', 'Laura Mendez | Gerente Comercial | Oficina Norte')
            ->assertJsonPath('datos.aprobador_asignacion_id_label', 'Laura Mendez | Gerente Comercial | Oficina Norte');

        $this->assertDatabaseHas('sucursales', [
            'organizacion_id' => $user->organizacion_activa_id,
            'slug' => 'oficina-norte',
            'nombre' => 'Oficina Norte',
        ]);
    }

    public function test_organization_resource_keeps_legacy_company_mirror_in_sync(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$user, $token] = $this->authenticateUser();
        $user->assignRole('admin');

        $organizationResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/organizations', [
                'nombre' => 'Organizacion Norte',
                'slug' => 'organizacion-norte',
                'activa' => true,
            ])
            ->assertOk();

        $organizationId = $organizationResponse->json('datos.id');

        $this->assertDatabaseHas('empresas', [
            'organizacion_id' => $organizationId,
            'slug' => 'organizacion-norte',
            'nombre' => 'Organizacion Norte',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/data/organizations/'.$organizationId, [
                'nombre' => 'Organizacion Norte Actualizada',
                'slug' => 'organizacion-norte',
                'activa' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('empresas', [
            'organizacion_id' => $organizationId,
            'slug' => 'organizacion-norte',
            'nombre' => 'Organizacion Norte Actualizada',
        ]);
    }

    public function test_resource_catalog_hides_legacy_transitional_resources_but_keeps_them_accessible(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$user, $token] = $this->authenticateUser();
        $user->assignRole('admin');

        $catalogResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/data/resources')
            ->assertOk();

        $resourceKeys = collect($catalogResponse->json('datos'))->pluck('key')->all();

        $this->assertContains('organizations', $resourceKeys);
        $this->assertContains('offices', $resourceKeys);
        $this->assertNotContains('tenant-companies', $resourceKeys);
        $this->assertNotContains('tenant-branches', $resourceKeys);
        $this->assertNotContains('tenant-teams', $resourceKeys);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/data/tenant-companies', [
                'nombre' => 'Legacy Company',
                'slug' => 'legacy-company',
            ])
            ->assertOk()
            ->assertJsonPath('datos.nombre', 'Legacy Company');
    }

    protected function authenticateUser(): array
    {
        $organization = Organizacion::query()->create([
            'nombre' => 'Acme Data Engine',
            'slug' => 'acme-data-engine',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $organization->id,
        ]);

        $user->organizaciones()->attach($organization->id);

        return [
            $user,
            app(AccessTokenService::class)->createForUser($user, 'phpunit'),
        ];
    }

    protected function authenticateUserWithTwoOrganizations(): array
    {
        $primaryOrganization = Organizacion::query()->create([
            'nombre' => 'Acme Data Engine Primary',
            'slug' => 'acme-data-engine-primary',
        ]);

        $secondaryOrganization = Organizacion::query()->create([
            'nombre' => 'Acme Data Engine Secondary',
            'slug' => 'acme-data-engine-secondary',
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
