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

    public function test_it_rejects_authenticated_user_without_permission(): void
    {
        $this
            ->withHeader('Authorization', 'Bearer '.$this->issueToken(false))
            ->getJson('/api/v1/modules')
            ->assertStatus(403)
            ->assertJsonPath('estado', 'error');
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
