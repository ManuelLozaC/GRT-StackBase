<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Database\Seeders\InstalacionBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstalacionBaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_official_base_installation_data(): void
    {
        $this->seed(InstalacionBaseSeeder::class);

        $this->assertDatabaseHas('organizaciones', [
            'nombre' => 'GRT SRL',
            'slug' => 'grt-srl',
        ]);

        $this->assertDatabaseHas('empresas', [
            'nombre' => 'GRT SRL',
            'slug' => 'grt-srl',
        ]);

        $this->assertDatabaseHas('sucursales', [
            'nombre' => 'TalentHub',
            'slug' => 'talenthub',
        ]);

        $this->assertDatabaseHas('oficinas', [
            'nombre' => 'TalentHub',
            'slug' => 'talenthub',
        ]);

        $this->assertDatabaseHas('personas', [
            'nombres' => 'Manuel',
            'apellido_paterno' => 'Loza',
            'correo' => 'mloza@grt.com.bo',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Manuel Loza',
            'alias' => 'mloza',
            'email' => 'mloza@grt.com.bo',
            'telefono' => '+591 70818566',
        ]);

        $this->assertDatabaseHas('asignaciones_laborales', [
            'estado' => 'active',
            'es_principal' => true,
        ]);
    }

    public function test_it_is_idempotent_and_does_not_duplicate_base_records(): void
    {
        $this->seed(InstalacionBaseSeeder::class);
        $this->seed(InstalacionBaseSeeder::class);

        $this->assertSame(1, \App\Models\Organizacion::query()->where('slug', 'grt-srl')->count());
        $this->assertSame(1, \App\Models\Empresa::query()->where('slug', 'grt-srl')->count());
        $this->assertSame(1, \App\Models\Sucursal::query()->where('slug', 'talenthub')->count());
        $this->assertSame(1, \App\Models\Oficina::query()->where('slug', 'talenthub')->count());
        $this->assertSame(1, \App\Models\Persona::query()->where('correo', 'mloza@grt.com.bo')->count());
        $this->assertSame(1, \App\Models\User::query()->where('email', 'mloza@grt.com.bo')->count());
        $this->assertSame(1, \App\Models\AsignacionLaboral::query()->count());
    }

    public function test_bootstrap_user_receives_current_admin_permissions(): void
    {
        $this->seed(InstalacionBaseSeeder::class);

        $user = User::query()->where('email', 'mloza@grt.com.bo')->firstOrFail();
        $permissions = $user->getAllPermissions()->pluck('name');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($permissions->contains('modules.view'));
        $this->assertTrue($permissions->contains('settings.view'));
        $this->assertTrue($permissions->contains('data-engine.access'));
        $this->assertTrue($permissions->contains('users.view'));
        $this->assertTrue($permissions->contains('roles.view'));
    }
}
