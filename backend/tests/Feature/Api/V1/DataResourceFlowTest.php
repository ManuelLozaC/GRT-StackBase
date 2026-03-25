<?php

namespace Tests\Feature\Api\V1;

use App\Core\Auth\Services\AccessTokenService;
use App\Core\Modules\ModuleRegistry;
use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
