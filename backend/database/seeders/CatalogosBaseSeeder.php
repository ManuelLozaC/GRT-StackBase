<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Ciudad;
use App\Models\Pais;
use Illuminate\Database\Seeder;

class CatalogosBaseSeeder extends Seeder
{
    public function run(): void
    {
        $bolivia = Pais::query()->withTrashed()->updateOrCreate(
            ['codigo_iso2' => 'BO'],
            [
                'nombre' => 'Bolivia',
                'codigo_iso3' => 'BOL',
                'gentilicio' => 'Boliviano',
                'activo' => true,
                'deleted_at' => null,
            ],
        );

        foreach ([
            'Santa Cruz de la Sierra',
            'La Paz',
            'Cochabamba',
            'Sucre',
            'Tarija',
            'Oruro',
            'Potosi',
            'Trinidad',
            'Cobija',
        ] as $indice => $nombre) {
            Ciudad::query()->withTrashed()->updateOrCreate(
                ['pais_id' => $bolivia->id, 'nombre' => $nombre],
                [
                    'codigo' => 'BO-CIUDAD-'.str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT),
                    'activo' => true,
                    'deleted_at' => null,
                ],
            );
        }
    }
}
