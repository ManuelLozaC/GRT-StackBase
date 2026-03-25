<?php

namespace App\Core\Http\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = 200,
    ): JsonResponse {
        return response()->json([
            'estado' => 'ok',
            'datos' => $data,
            'mensaje' => $message,
            'meta' => $meta,
            'errores' => [],
        ], $status);
    }

    protected function errorResponse(
        string $message,
        array $errors = [],
        array $meta = [],
        int $status = 422,
    ): JsonResponse {
        return response()->json([
            'estado' => 'error',
            'datos' => null,
            'mensaje' => $message,
            'meta' => $meta,
            'errores' => $errors,
        ], $status);
    }
}
