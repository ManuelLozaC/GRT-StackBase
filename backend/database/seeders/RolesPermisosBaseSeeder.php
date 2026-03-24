<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermisosBaseSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.resetear_password',
            'personas.ver',
            'personas.crear',
            'personas.editar',
            'oficinas.ver',
            'oficinas.crear',
            'oficinas.editar',
            'organizaciones.ver',
            'organizaciones.editar',
            'asignaciones.ver',
            'asignaciones.crear',
            'asignaciones.editar',
        ];

        foreach ($permisos as $permiso) {
            Permission::query()->updateOrCreate([
                'name' => $permiso,
                'guard_name' => 'sanctum',
            ]);
        }

        $roles = [
            'superusuario' => $permisos,
            'administrador_sistema_cliente' => [
                'usuarios.ver',
                'usuarios.crear',
                'usuarios.editar',
                'usuarios.resetear_password',
                'personas.ver',
                'personas.crear',
                'personas.editar',
                'oficinas.ver',
                'asignaciones.ver',
                'asignaciones.crear',
                'asignaciones.editar',
            ],
            'gerente_sucursal' => [
                'usuarios.ver',
                'personas.ver',
                'oficinas.ver',
                'asignaciones.ver',
            ],
            'ejecutivo_ventas' => [
                'personas.ver',
                'oficinas.ver',
            ],
        ];

        foreach ($roles as $nombre => $permisosRol) {
            $rol = Role::query()->updateOrCreate([
                'name' => $nombre,
                'guard_name' => 'sanctum',
                'oficina_id' => null,
            ]);

            $rol->syncPermissions($permisosRol);
        }
    }
}
