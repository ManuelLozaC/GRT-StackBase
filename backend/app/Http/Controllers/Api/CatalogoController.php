<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Ciudad;
use App\Models\Division;
use App\Models\Oficina;
use App\Models\Organizacion;
use App\Support\RespuestaApi;
use Spatie\Permission\Models\Role;

class CatalogoController extends Controller
{
    public function formularioUsuarios()
    {
        return RespuestaApi::ok([
            'organizaciones' => Organizacion::query()->orderBy('nombre')->get(['id', 'nombre']),
            'oficinas' => Oficina::query()->orderBy('nombre')->get(['id', 'nombre', 'organizacion_id']),
            'ciudades' => Ciudad::query()->orderBy('nombre')->get(['id', 'nombre', 'pais_id']),
            'divisiones' => Division::query()->orderBy('nombre')->get(['id', 'nombre']),
            'areas' => Area::query()->orderBy('nombre')->get(['id', 'nombre', 'division_id']),
            'cargos' => Cargo::query()->orderBy('nombre')->get(['id', 'nombre', 'es_aprobador']),
            'roles' => Role::query()
                ->whereNull('oficina_id')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
