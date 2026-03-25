<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Core\Auth\Services\AccessTokenService;
use App\Models\Organizacion;
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
