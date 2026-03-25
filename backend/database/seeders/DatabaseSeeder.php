<?php

declare(strict_types=1);

namespace Database\Seeders;

<<<<<<< HEAD
use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
=======
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
<<<<<<< HEAD
        $organizacion = Organizacion::query()->updateOrCreate(
            ['slug' => 'stackbase-demo'],
            [
                'nombre' => 'StackBase Demo',
                'metadata' => [
                    'kind' => 'core-demo',
                ],
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@stackbase.local'],
            [
                'name' => 'StackBase Admin',
                'password' => 'password',
                'organizacion_activa_id' => $organizacion->id,
            ],
        );

        $admin->organizaciones()->syncWithoutDetaching([$organizacion->id]);

        $this->call(RolePermissionSeeder::class);
=======
        $this->call([
            CatalogosBaseSeeder::class,
            RolesPermisosBaseSeeder::class,
            InstalacionBaseSeeder::class,
        ]);
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
    }
}
