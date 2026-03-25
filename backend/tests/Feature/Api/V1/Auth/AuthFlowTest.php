<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Core\Auth\Services\AccessTokenService;
use App\Models\AsignacionLaboral;
use App\Models\Cargo;
use App\Models\Oficina;
use App\Models\Organizacion;
use App\Models\Persona;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_retrieve_profile(): void
    {
        $organizacion = Organizacion::query()->create([
            'nombre' => 'Acme Central',
            'slug' => 'acme-central',
        ]);

        $user = User::factory()->create([
            'email' => 'admin@stackbase.local',
            'password' => 'password',
            'organizacion_activa_id' => $organizacion->id,
        ]);

        $user->organizaciones()->attach($organizacion->id);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@stackbase.local',
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('estado', 'ok');

        $token = $loginResponse->json('datos.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('datos.email', 'admin@stackbase.local')
            ->assertJsonPath('datos.organizacion_activa.nombre', 'Acme Central')
            ->assertJsonFragment([
                'slug' => 'acme-central',
            ]);
    }

    public function test_user_can_login_with_alias_and_retrieve_profile(): void
    {
        $organizacion = Organizacion::query()->create([
            'nombre' => 'Alias Org',
            'slug' => 'alias-org',
        ]);

        $user = User::factory()->create([
            'alias' => 'mloza',
            'email' => 'mloza@grt.com.bo',
            'password' => 'admin1984!',
            'organizacion_activa_id' => $organizacion->id,
        ]);

        $user->organizaciones()->attach($organizacion->id);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'mloza',
            'password' => 'admin1984!',
            'device_name' => 'phpunit-alias',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('datos.user.alias', 'mloza')
            ->assertJsonPath('datos.user.email', 'mloza@grt.com.bo');
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'admin@stackbase.local',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@stackbase.local',
            'password' => 'incorrecta',
        ])
            ->assertStatus(422)
            ->assertJsonPath('estado', 'error');
    }

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nuevo Admin',
            'email' => 'nuevo@stackbase.local',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'organization_name' => 'Nuevo Tenant Demo',
            'device_name' => 'phpunit-register',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('datos.user.email', 'nuevo@stackbase.local')
            ->assertJsonPath('datos.user.organizacion_activa.nombre', 'Nuevo Tenant Demo');

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@stackbase.local',
        ]);

        $this->assertDatabaseHas('organizaciones', [
            'nombre' => 'Nuevo Tenant Demo',
        ]);
    }

    public function test_user_can_request_and_reset_password(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@stackbase.local',
            'password' => 'password',
        ]);

        $forgotResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset@stackbase.local',
        ]);

        $forgotResponse
            ->assertOk()
            ->assertJsonPath('mensaje', 'Si el email existe, se genero un token de recuperacion');

        $token = $forgotResponse->json('meta.debug_reset_token_preview');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset@stackbase.local',
            'token' => $token,
            'password' => 'nuevaPassword123',
            'password_confirmation' => 'nuevaPassword123',
        ])
            ->assertOk()
            ->assertJsonPath('mensaje', 'Contrasena restablecida correctamente');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'reset@stackbase.local',
            'password' => 'nuevaPassword123',
        ])
            ->assertOk()
            ->assertJsonPath('estado', 'ok');
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        User::factory()->create([
            'email' => 'reset@stackbase.local',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset@stackbase.local',
            'token' => 'token-invalido',
            'password' => 'nuevaPassword123',
            'password_confirmation' => 'nuevaPassword123',
        ])
            ->assertStatus(422)
            ->assertJsonPath('estado', 'error');
    }

    public function test_user_can_switch_active_organization(): void
    {
        $organizacionA = Organizacion::query()->create([
            'nombre' => 'Acme Central',
            'slug' => 'acme-central',
        ]);

        $organizacionB = Organizacion::query()->create([
            'nombre' => 'Acme Norte',
            'slug' => 'acme-norte',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $organizacionA->id,
        ]);

        $user->organizaciones()->attach([
            $organizacionA->id,
            $organizacionB->id,
        ]);

        $token = app(AccessTokenService::class)->createForUser($user, 'phpunit');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $organizacionB->id,
            ])
            ->assertOk()
            ->assertJsonPath('datos.organizacion_activa.slug', 'acme-norte');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'organizacion_activa_id' => $organizacionB->id,
        ]);
    }

    public function test_user_can_switch_active_work_assignment_within_active_organization(): void
    {
        $organizacion = Organizacion::query()->create([
            'nombre' => 'Acme Central',
            'slug' => 'acme-central',
        ]);

        $persona = Persona::query()->create([
            'organizacion_id' => $organizacion->id,
            'nombres' => 'Maria',
            'apellido_paterno' => 'Suarez',
            'correo' => 'maria@acme.test',
        ]);

        $user = User::factory()->create([
            'persona_id' => $persona->id,
            'organizacion_activa_id' => $organizacion->id,
        ]);
        $user->organizaciones()->attach($organizacion->id);

        $oficinaA = Oficina::query()->create([
            'organizacion_id' => $organizacion->id,
            'nombre' => 'Sucursal Centro',
            'slug' => 'sucursal-centro',
            'activa' => true,
        ]);

        $oficinaB = Oficina::query()->create([
            'organizacion_id' => $organizacion->id,
            'nombre' => 'Sucursal Norte',
            'slug' => 'sucursal-norte',
            'activa' => true,
        ]);

        $cargoA = Cargo::query()->create([
            'organizacion_id' => $organizacion->id,
            'nombre' => 'Ejecutiva de Ventas',
            'slug' => 'ejecutiva-de-ventas',
            'activa' => true,
        ]);

        $cargoB = Cargo::query()->create([
            'organizacion_id' => $organizacion->id,
            'nombre' => 'Gerente Comercial',
            'slug' => 'gerente-comercial',
            'activa' => true,
        ]);

        $assignmentA = AsignacionLaboral::query()->create([
            'organizacion_id' => $organizacion->id,
            'persona_id' => $persona->id,
            'user_id' => $user->id,
            'oficina_id' => $oficinaA->id,
            'cargo_id' => $cargoA->id,
            'es_principal' => true,
            'estado' => 'active',
            'fecha_inicio' => '2026-03-01',
        ]);

        $assignmentB = AsignacionLaboral::query()->create([
            'organizacion_id' => $organizacion->id,
            'persona_id' => $persona->id,
            'user_id' => $user->id,
            'oficina_id' => $oficinaB->id,
            'cargo_id' => $cargoB->id,
            'es_principal' => false,
            'estado' => 'active',
            'fecha_inicio' => '2026-03-10',
        ]);

        $user->forceFill([
            'active_work_assignment_id' => $assignmentA->id,
        ])->save();

        $token = app(AccessTokenService::class)->createForUser($user, 'phpunit');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('datos.asignacion_laboral_activa.id', $assignmentA->id)
            ->assertJsonPath('datos.asignaciones_laborales_disponibles.1.id', $assignmentB->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-work-assignment', [
                'asignacion_laboral_id' => $assignmentB->id,
            ])
            ->assertOk()
            ->assertJsonPath('datos.asignacion_laboral_activa.id', $assignmentB->id)
            ->assertJsonPath('datos.asignacion_laboral_activa.oficina.nombre', 'Sucursal Norte')
            ->assertJsonPath('datos.asignacion_laboral_activa.cargo.nombre', 'Gerente Comercial');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'active_work_assignment_id' => $assignmentB->id,
        ]);
    }

    public function test_user_cannot_switch_to_an_organization_without_membership(): void
    {
        $organizacionA = Organizacion::query()->create([
            'nombre' => 'Acme Central',
            'slug' => 'acme-central',
        ]);

        $organizacionB = Organizacion::query()->create([
            'nombre' => 'Acme Norte',
            'slug' => 'acme-norte',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $organizacionA->id,
        ]);

        $user->organizaciones()->attach($organizacionA->id);

        $token = app(AccessTokenService::class)->createForUser($user, 'phpunit');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/active-organization', [
                'organizacion_id' => $organizacionB->id,
            ])
            ->assertStatus(403)
            ->assertJsonPath('estado', 'error');
    }

    public function test_admin_can_list_users_update_roles_and_impersonate(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $organizacion = Organizacion::query()->create([
            'nombre' => 'Tenant Admin',
            'slug' => 'tenant-admin',
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@stackbase.local',
            'organizacion_activa_id' => $organizacion->id,
        ]);
        $admin->organizaciones()->attach($organizacion->id);
        $admin->assignRole('admin');

        $targetUser = User::factory()->create([
            'email' => 'member@stackbase.local',
            'organizacion_activa_id' => $organizacion->id,
        ]);
        $targetUser->organizaciones()->attach($organizacion->id);

        $adminToken = app(AccessTokenService::class)->createForUser($admin, 'phpunit-admin');

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('meta.available_roles.0', 'admin');

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson('/api/v1/users/'.$targetUser->id.'/roles', [
                'roles' => ['admin'],
            ])
            ->assertOk()
            ->assertJsonPath('datos.roles.0', 'admin');

        $impersonationResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/auth/impersonate/'.$targetUser->id)
            ->assertOk()
            ->assertJsonPath('datos.user.email', 'member@stackbase.local')
            ->assertJsonPath('datos.user.impersonation.active', true);

        $impersonatedToken = $impersonationResponse->json('datos.token');

        $this->withHeader('Authorization', 'Bearer '.$impersonatedToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('datos.email', 'member@stackbase.local')
            ->assertJsonPath('datos.impersonation.active', true)
            ->assertJsonPath('datos.impersonation.impersonated_by.email', 'admin@stackbase.local');

        $this->withHeader('Authorization', 'Bearer '.$impersonatedToken)
            ->postJson('/api/v1/auth/impersonation/leave')
            ->assertOk()
            ->assertJsonPath('datos.user.email', 'admin@stackbase.local')
            ->assertJsonPath('datos.user.impersonation.active', false);
    }

    public function test_context_permissions_can_authorize_user_management_without_global_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $organizacion = Organizacion::query()->create([
            'nombre' => 'Tenant Context',
            'slug' => 'tenant-context',
        ]);

        $persona = Persona::query()->create([
            'organizacion_id' => $organizacion->id,
            'nombres' => 'Lucia',
            'apellido_paterno' => 'Rojas',
            'correo' => 'lucia@stackbase.local',
        ]);

        $oficina = Oficina::query()->create([
            'organizacion_id' => $organizacion->id,
            'nombre' => 'Sucursal Centro',
            'slug' => 'sucursal-centro',
            'activa' => true,
        ]);

        $user = User::factory()->create([
            'persona_id' => $persona->id,
            'email' => 'lucia@stackbase.local',
            'organizacion_activa_id' => $organizacion->id,
        ]);
        $user->organizaciones()->attach($organizacion->id);

        $assignment = AsignacionLaboral::query()->create([
            'organizacion_id' => $organizacion->id,
            'persona_id' => $persona->id,
            'user_id' => $user->id,
            'oficina_id' => $oficina->id,
            'es_principal' => true,
            'estado' => 'active',
            'fecha_inicio' => '2026-03-01',
            'metadata' => [
                'context_permissions' => [
                    'users.manage_roles',
                ],
            ],
        ]);

        $user->forceFill([
            'active_work_assignment_id' => $assignment->id,
        ])->save();

        $token = app(AccessTokenService::class)->createForUser($user, 'phpunit-context');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('datos.context_permissions.0', 'users.manage_roles');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('mensaje', 'Usuarios listados');
    }

    public function test_admin_can_create_update_activate_and_reset_users(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $organizacion = Organizacion::query()->create([
            'nombre' => 'Tenant Admin',
            'slug' => 'tenant-admin',
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@stackbase.local',
            'organizacion_activa_id' => $organizacion->id,
        ]);
        $admin->organizaciones()->attach($organizacion->id);
        $admin->assignRole('admin');

        $persona = Persona::query()->create([
            'organizacion_id' => $organizacion->id,
            'nombres' => 'Maria',
            'apellido_paterno' => 'Suarez',
            'correo' => 'maria@test.dev',
        ]);

        $adminToken = app(AccessTokenService::class)->createForUser($admin, 'phpunit-admin');

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/users', [
                'persona_id' => $persona->id,
                'name' => 'Maria Suarez',
                'alias' => 'msuarez',
                'email' => 'maria.suarez@test.dev',
                'telefono' => '70000012',
                'activo' => true,
                'roles' => ['admin'],
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ])
            ->assertOk()
            ->assertJsonPath('datos.alias', 'msuarez')
            ->assertJsonPath('datos.persona.id', $persona->id);

        $userId = $createResponse->json('datos.id');

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson('/api/v1/users/'.$userId, [
                'name' => 'Maria Suarez Actualizada',
                'telefono' => '70000013',
                'activo' => false,
            ])
            ->assertOk()
            ->assertJsonPath('datos.name', 'Maria Suarez Actualizada')
            ->assertJsonPath('datos.activo', false);

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson('/api/v1/users/'.$userId.'/status', [
                'activo' => true,
            ])
            ->assertOk()
            ->assertJsonPath('datos.activo', true);

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/users/'.$userId.'/reset-password', [
                'password' => 'NuevaPassword123',
                'password_confirmation' => 'NuevaPassword123',
            ])
            ->assertOk()
            ->assertJsonPath('datos.primer_acceso_pendiente', true);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'msuarez',
            'password' => 'NuevaPassword123',
        ])
            ->assertOk()
            ->assertJsonPath('datos.user.email', 'maria.suarez@test.dev');
    }

    public function test_user_can_create_list_and_revoke_api_tokens(): void
    {
        $organizacion = Organizacion::query()->create([
            'nombre' => 'API Access',
            'slug' => 'api-access',
        ]);

        $user = User::factory()->create([
            'organizacion_activa_id' => $organizacion->id,
        ]);
        $user->organizaciones()->attach($organizacion->id);

        $token = app(AccessTokenService::class)->createForUser($user, 'phpunit');

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/api-tokens', [
                'name' => 'Integracion Test',
                'expires_in_days' => 7,
            ])
            ->assertOk()
            ->assertJsonPath('datos.token.name', 'Integracion Test');

        $createdTokenId = $createResponse->json('datos.token.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/api-tokens')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Integracion Test',
                'type' => 'api-client',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/auth/api-tokens/'.$createdTokenId)
            ->assertOk()
            ->assertJsonPath('mensaje', 'Token API revocado');
    }
}
