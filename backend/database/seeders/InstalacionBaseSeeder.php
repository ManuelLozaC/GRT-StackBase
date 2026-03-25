<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Database\Seeder;

class InstalacionBaseSeeder extends Seeder
{
    public function run(): void
    {
        $organizacion = Organizacion::query()->updateOrCreate(
            ['slug' => 'stackbase-client-demo'],
            [
                'nombre' => 'StackBase Client Demo',
                'metadata' => [
                    'kind' => 'client-bootstrap',
                ],
            ],
        );

        $usuario = User::query()->updateOrCreate(
            ['email' => 'cliente.admin@stackbase.local'],
            [
                'name' => 'Cliente Admin',
                'password' => 'password',
                'organizacion_activa_id' => $organizacion->id,
            ],
        );

        $usuario->organizaciones()->syncWithoutDetaching([$organizacion->id]);

        $this->call(RolePermissionSeeder::class);
    }
}
