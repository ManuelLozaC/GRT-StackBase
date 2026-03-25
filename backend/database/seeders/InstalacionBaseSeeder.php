<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AsignacionLaboral;
use App\Models\Cargo;
use App\Models\Division;
use App\Models\Empresa;
use App\Models\Oficina;
use App\Models\Organizacion;
use App\Models\Persona;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

class InstalacionBaseSeeder extends Seeder
{
    public function run(): void
    {
        $organizacion = Organizacion::query()->updateOrCreate(
            ['slug' => 'grt-srl'],
            [
                'nombre' => 'GRT SRL',
                'activa' => true,
                'metadata' => [
                    'kind' => 'platform-bootstrap',
                    'tenant_type' => 'grt-root',
                    'domain_decision' => 'organizacion_eq_empresa',
                    'pais' => 'Bolivia',
                    'ciudad' => 'Santa Cruz de la Sierra',
                ],
            ],
        );

        $empresa = Empresa::query()->updateOrCreate(
            [
                'organizacion_id' => $organizacion->id,
                'slug' => 'grt-srl',
            ],
            [
                'nombre' => 'GRT SRL',
                'metadata' => [
                    'kind' => 'organization-mirror',
                    'same_as_organizacion' => true,
                ],
            ],
        );

        $sucursal = Sucursal::query()->updateOrCreate(
            [
                'organizacion_id' => $organizacion->id,
                'slug' => 'talenthub',
            ],
            [
                'empresa_id' => $empresa->id,
                'nombre' => 'TalentHub',
                'metadata' => [
                    'kind' => 'oficina-principal',
                    'pais' => 'Bolivia',
                    'ciudad' => 'Santa Cruz de la Sierra',
                ],
            ],
        );

        $oficina = Oficina::query()->updateOrCreate(
            [
                'organizacion_id' => $organizacion->id,
                'slug' => 'talenthub',
            ],
            [
                'nombre' => 'TalentHub',
                'codigo' => 'TALENTHUB',
                'telefono' => '+591 70818566',
                'direccion' => 'Santa Cruz de la Sierra',
                'ciudad' => 'Santa Cruz de la Sierra',
                'pais' => 'Bolivia',
                'activa' => true,
                'metadata' => [
                    'kind' => 'oficina-principal',
                    'legacy_sucursal_id' => $sucursal->id,
                ],
            ],
        );

        $division = Division::query()->updateOrCreate(
            [
                'organizacion_id' => $organizacion->id,
                'slug' => 'direccion-general',
            ],
            [
                'nombre' => 'Direccion General',
                'descripcion' => 'Division base del stack para la instalacion inicial.',
                'activa' => true,
            ],
        );

        $cargo = Cargo::query()->updateOrCreate(
            [
                'organizacion_id' => $organizacion->id,
                'slug' => 'superusuario-administrador',
            ],
            [
                'nombre' => 'Superusuario Administrador',
                'descripcion' => 'Cargo base con alcance administrativo global sobre el stack.',
                'activa' => true,
            ],
        );

        $persona = Persona::query()->updateOrCreate(
            [
                'organizacion_id' => $organizacion->id,
                'correo' => 'mloza@grt.com.bo',
            ],
            [
                'nombres' => 'Manuel',
                'apellido_paterno' => 'Loza',
                'apellido_materno' => null,
                'documento_identidad' => 'PENDIENTE',
                'telefono' => '+591 70818566',
                'direccion' => 'Santa Cruz de la Sierra',
                'sexo' => 'masculino',
                'fecha_nacimiento' => '1984-08-02',
                'ciudad' => 'Santa Cruz de la Sierra',
                'pais' => 'Bolivia',
                'activa' => true,
                'metadata' => [
                    'seed_origin' => 'InstalacionBaseSeeder',
                ],
            ],
        );

        $usuario = User::query()->updateOrCreate(
            ['email' => 'mloza@grt.com.bo'],
            [
                'persona_id' => $persona->id,
                'name' => 'Manuel Loza',
                'alias' => 'mloza',
                'telefono' => '+591 70818566',
                'password' => 'admin1984!',
                'activo' => true,
                'primer_acceso_pendiente' => false,
                'expira_password_en' => Carbon::now()->addMonths(6),
                'organizacion_activa_id' => $organizacion->id,
            ],
        );

        $usuario->organizaciones()->syncWithoutDetaching([$organizacion->id]);

        $asignacion = AsignacionLaboral::query()->updateOrCreate(
            [
                'organizacion_id' => $organizacion->id,
                'persona_id' => $persona->id,
                'oficina_id' => $oficina->id,
            ],
            [
                'user_id' => $usuario->id,
                'division_id' => $division->id,
                'cargo_id' => $cargo->id,
                'jefe_asignacion_id' => null,
                'aprobador_asignacion_id' => null,
                'es_principal' => true,
                'estado' => 'active',
                'fecha_inicio' => '2026-01-01',
                'fecha_fin' => null,
                'metadata' => [
                    'rol_base' => 'superusuario',
                    'oficina_principal' => true,
                    'context_permissions' => [
                        'modules.manage',
                        'settings.manage',
                        'integrations.manage',
                        'users.manage_roles',
                        'users.impersonate',
                        'tenancy.manage',
                        'security.manage',
                    ],
                ],
            ],
        );

        $usuario->forceFill([
            'active_work_assignment_id' => $asignacion->id,
        ])->save();

        $this->call(RolePermissionSeeder::class);
    }
}
