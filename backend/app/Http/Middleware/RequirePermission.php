<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse('No autenticado', 401);
        }

        if (! $user->can($permission)) {
            return $this->errorResponse('No autorizado', 403);
        }

        return $next($request);
    }

    protected function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'estado' => 'error',
            'datos' => null,
            'mensaje' => $message,
            'meta' => [],
            'errores' => [],
        ], $status);
    }
}
