<?php

namespace Tests\Feature\Api\V1;

use App\Core\Auth\Services\AccessTokenService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_installed_modules(): void
    {
        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->issueToken())
            ->getJson('/api/v1/modules');

        $response
            ->assertOk()
            ->assertJsonPath('estado', 'ok')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment([
                'key' => 'demo-platform',
                'enabled' => false,
                'is_demo' => true,
            ]);
    }

    public function test_it_can_toggle_a_module_state(): void
    {
        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->issueToken(true))
            ->patchJson('/api/v1/modules/demo-platform', [
                'enabled' => true,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('estado', 'ok')
            ->assertJsonPath('datos.key', 'demo-platform')
            ->assertJsonPath('datos.enabled', true);

        $this
            ->withHeader('Authorization', 'Bearer '.$this->issueToken(true))
            ->getJson('/api/v1/modules')
            ->assertJsonFragment([
                'key' => 'demo-platform',
                'enabled' => true,
            ]);
    }

    public function test_it_rejects_disabling_a_protected_core_module(): void
    {
        $this
            ->withHeader('Authorization', 'Bearer '.$this->issueToken(true))
            ->patchJson('/api/v1/modules/core-platform', [
                'enabled' => false,
            ])
            ->assertStatus(422)
            ->assertJsonPath('estado', 'error')
            ->assertJsonPath('mensaje', 'No se puede deshabilitar un modulo protegido del core.');
    }

    public function test_it_allows_authenticated_user_to_list_modules_without_permission(): void
    {
        $this
            ->withHeader('Authorization', 'Bearer '.$this->issueToken(false))
            ->getJson('/api/v1/modules')
            ->assertOk()
            ->assertJsonPath('estado', 'ok');
    }

    public function test_it_rejects_module_toggle_without_permission(): void
    {
        $this
            ->withHeader('Authorization', 'Bearer '.$this->issueToken(false))
            ->patchJson('/api/v1/modules/demo-platform', [
                'enabled' => true,
            ])
            ->assertStatus(403)
            ->assertJsonPath('estado', 'error');
    }

    public function test_it_can_read_and_update_module_settings(): void
    {
        $token = $this->issueToken(true);

        $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/modules/demo-platform/settings')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment([
                'key' => 'default_file_ttl_minutes',
                'value' => 30,
            ]);

        $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/modules/demo-platform/settings', [
                'settings' => [
                    'default_file_ttl_minutes' => 45,
                    'notification_default_level' => 'warning',
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'key' => 'default_file_ttl_minutes',
                'value' => 45,
            ])
            ->assertJsonFragment([
                'key' => 'notification_default_level',
                'value' => 'warning',
            ]);
    }

    protected function issueToken(bool $withPermission = true): string
    {
        $user = User::factory()->create();

        if ($withPermission) {
            $permission = Permission::query()->firstOrCreate([
                'name' => 'modules.manage',
                'guard_name' => 'web',
            ]);

            $user->givePermissionTo($permission);
        }

        return app(AccessTokenService::class)->createForUser($user, 'phpunit');
    }
}
