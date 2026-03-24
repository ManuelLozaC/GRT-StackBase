<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;

class RespuestaApi
{
    public static function ok(
        mixed $datos = null,
        string $mensaje = 'Operación exitosa',
        array $meta = [],
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'estado' => 'ok',
            'datos' => $datos,
            'mensaje' => $mensaje,
            'meta' => $meta,
            'errores' => [],
        ], $status);
    }

    public static function error(
        string $mensaje = 'Ocurrió un error',
        array $errores = [],
        array $meta = [],
        int $status = 422
    ): JsonResponse {
        return response()->json([
            'estado' => 'error',
            'datos' => null,
            'mensaje' => $mensaje,
            'meta' => $meta,
            'errores' => $errores,
        ], $status);
    }
}
