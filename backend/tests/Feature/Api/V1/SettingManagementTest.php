<?php

namespace Tests\Feature\Api\V1;

use App\Core\Auth\Services\AccessTokenService;
use App\Models\Organizacion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_and_update_global_and_company_settings(): void
    {
        [$user, $token] = $this->authenticateAdmin();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/settings/global')
            ->assertOk()
            ->assertJsonFragment([
                'key' => 'support_email',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/settings/global', [
                'support_email' => 'ops@stackbase.test',
                'app_banner_enabled' => true,
                'app_banner_message' => 'Mantenimiento planificado',
                'feature_global_error_toasts' => false,
                'ui_preset' => 'Nora',
                'ui_primary_color' => 'sky',
                'ui_surface_palette' => 'ocean',
                'ui_menu_mode' => 'overlay',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'key' => 'support_email',
                'value' => 'ops@stackbase.test',
            ])
            ->assertJsonFragment([
                'key' => 'ui_preset',
                'value' => 'Nora',
            ])
            ->assertJsonFragment([
                'key' => 'ui_menu_mode',
                'value' => 'overlay',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/settings/company', [
                'locale' => 'en-US',
                'currency_code' => 'USD',
            ])
            ->assertOk()
            ->assertJsonPath('mensaje', 'Settings de empresa actualizados')
            ->assertJsonFragment([
                'key' => 'locale',
                'value' => 'en-US',
            ])
            ->assertJsonFragment([
                'key' => 'currency_code',
                'value' => 'USD',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/settings/organization')
            ->assertOk()
            ->assertJsonFragment([
                'key' => 'locale',
                'value' => 'en-US',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/settings/bootstrap')
            ->assertOk()
            ->assertJsonPath('datos.feature_flags.feature_global_error_toasts', false)
            ->assertJsonPath('datos.global.0.key', 'support_email')
            ->assertJsonPath('datos.company.0.key', 'locale')
            ->assertJsonPath('datos.organization.0.key', 'locale');
    }

    public function test_regular_user_can_manage_only_own_preferences(): void
    {
        [$user, $token] = $this->authenticateUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/settings/me', [
                'theme' => 'dark',
                'notifications_internal' => false,
                'dense_tables' => true,
                'data_engine_preferences' => [
                    'demo-contacts' => [
                        'visible_columns' => ['nombre', 'email'],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'key' => 'theme',
                'value' => 'dark',
            ])
            ->assertJsonFragment([
                'key' => 'notifications_internal',
                'value' => false,
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/settings/me')
            ->assertOk()
            ->assertJsonFragment([
                'key' => 'dense_tables',
                'value' => true,
            ])
            ->assertJsonFragment([
                'key' => 'data_engine_preferences',
                'value' => [
                    'demo-contacts' => [
                        'visible_columns' => ['nombre', 'email'],
                    ],
                ],
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/settings/global')
            ->assertForbidden();
    }

    protected function authenticateAdmin(): array
    {
        [$user, $token] = $this->authenticateUser();
        $this->seed(RolePermissionSeeder::class);
        $user->assignRole('admin');

        return [$user->fresh(), $token];
    }

    protected function authenticateUser(): array
    {
        $organization = Organizacion::query()->create([
            'nombre' => 'Settings Workspace',
            'slug' => 'settings-workspace',
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
}
