<?php

namespace Database\Seeders;

use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
    }
}
