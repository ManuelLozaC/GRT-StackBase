<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AsignacionLaboral;
use App\Models\Ciudad;
use App\Models\Oficina;
use App\Models\Organizacion;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class InstalacionBaseSeeder extends Seeder
{
    public function run(): void
    {
        $ciudad = Ciudad::query()->where('nombre', 'Santa Cruz de la Sierra')->firstOrFail();

        $organizacion = Organizacion::query()->withTrashed()->updateOrCreate(
            ['nombre' => 'GRT SRL'],
            [
                'nombre_comercial' => 'GRT SRL',
                'email' => 'mloza@grt.com.bo',
                'telefono' => '+591 70818566',
                'direccion' => 'Santa Cruz de la Sierra',
                'pais_id' => $ciudad->pais_id,
                'ciudad_id' => $ciudad->id,
                'activa' => true,
                'deleted_at' => null,
            ],
        );

        $oficina = Oficina::query()->withTrashed()->updateOrCreate(
            ['organizacion_id' => $organizacion->id, 'nombre' => 'TalentHub'],
            [
                'ciudad_id' => $ciudad->id,
                'codigo' => 'TALENTHUB',
                'direccion' => 'Santa Cruz de la Sierra',
                'telefono' => '+591 70818566',
                'es_principal' => true,
                'activa' => true,
                'deleted_at' => null,
            ],
        );

        $persona = Persona::query()->withTrashed()->updateOrCreate(
            ['organizacion_id' => $organizacion->id, 'numero_documento' => 'BASE-MLOZA'],
            [
                'ciudad_id' => $ciudad->id,
                'tipo_documento' => 'CI',
                'nombres' => 'Manuel',
                'apellido_paterno' => 'Loza',
                'apellido_materno' => null,
                'genero' => 'masculino',
                'fecha_nacimiento' => '1984-08-02',
                'email' => 'mloza@grt.com.bo',
                'telefono' => '+591 70818566',
                'direccion' => 'Santa Cruz de la Sierra',
                'activo' => true,
                'deleted_at' => null,
            ],
        );

        $usuario = User::query()->withTrashed()->updateOrCreate(
            ['email' => 'mloza@grt.com.bo'],
            [
                'organizacion_id' => $organizacion->id,
                'persona_id' => $persona->id,
                'alias' => 'mloza',
                'nombre_mostrar' => 'Manuel Loza',
                'telefono' => '+591 70818566',
                'password' => Hash::make('admin1984!'),
                'es_superusuario' => true,
                'debe_cambiar_password' => true,
                'activo' => true,
                'deleted_at' => null,
            ],
        );

        AsignacionLaboral::query()->withTrashed()->updateOrCreate(
            [
                'organizacion_id' => $organizacion->id,
                'usuario_id' => $usuario->id,
                'oficina_id' => $oficina->id,
            ],
            [
                'persona_id' => $persona->id,
                'es_principal' => true,
                'activa' => true,
                'fecha_inicio' => now()->toDateString(),
                'deleted_at' => null,
            ],
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $usuario->syncRoles(['superusuario']);
    }
}
