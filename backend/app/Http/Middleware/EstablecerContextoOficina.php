<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class EstablecerContextoOficina
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return $next($request);
        }

        $oficinaId = $request->header('X-Oficina-Id');

        if ($oficinaId === null && method_exists($usuario, 'asignacionesLaborales')) {
            $oficinaId = $usuario->asignacionesLaborales()
                ->where('activa', true)
                ->orderByDesc('es_principal')
                ->value('oficina_id');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($oficinaId);
        $request->attributes->set('oficina_id_contexto', $oficinaId);

        return $next($request);
    }
}
